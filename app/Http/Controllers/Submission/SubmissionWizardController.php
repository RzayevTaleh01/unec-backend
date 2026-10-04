<?php

namespace App\Http\Controllers\Submission;

use App\Exceptions\WorkflowException;
use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\User;
use App\Services\SubmissionWorkflow;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Single-page submission wizard. The five steps (start → file → details → authors → confirm)
 * are switched client-side (public/assets/js/wizard.js) and posted together; the server
 * re-validates everything, either as a draft (relaxed) or as the final submission.
 */
class SubmissionWizardController extends Controller
{
    public const CHECKLIST = ['guidelines', 'original', 'urls', 'format', 'permissions'];

    public const FILE_TYPE_RULES = ['file', 'mimes:doc,docx,rtf,odt,pdf', 'max:20480'];

    /** Used by the revision upload on the submission page. */
    public const FILE_RULES = ['required', 'file', 'mimes:doc,docx,rtf,odt,pdf', 'max:20480'];

    public function __construct(private SubmissionWorkflow $workflow)
    {
    }

    public function start(Request $request)
    {
        return view('submit.wizard', [
            'article' => null,
            'drafts' => $request->user()->submissions()->where('status', 'draft')->latest()->get(),
            'checklist' => self::CHECKLIST,
        ]);
    }

    public function edit(Request $request, Article $article)
    {
        $this->authorizeDraft($request, $article);

        return view('submit.wizard', [
            'article' => $article->load('authors', 'files'),
            'drafts' => collect(),
            'checklist' => self::CHECKLIST,
        ]);
    }

    public function store(Request $request)
    {
        return $this->persist($request, null);
    }

    public function update(Request $request, Article $article)
    {
        $this->authorizeDraft($request, $article);

        return $this->persist($request, $article);
    }

    /** Old per-step URLs (bookmarks, e-mails) now land on the single-page wizard. */
    public function legacyStep(Request $request, Article $article)
    {
        return redirect()->route('submit.edit', $article);
    }

    public function destroy(Request $request, Article $article)
    {
        $this->authorizeDraft($request, $article);

        Storage::disk('local')->deleteDirectory("articles/{$article->id}");
        $article->delete();

        return redirect()->route('profile.submissions')->with('status', __('site.wizard.draft_deleted'));
    }

    private function persist(Request $request, ?Article $article)
    {
        $final = $request->input('mode') !== 'draft';
        $hasFile = (bool) $article?->files()->exists();

        $this->normaliseAuthors($request);

        $data = $request->validate($this->rules($final, $hasFile));

        $article = DB::transaction(function () use ($request, $article, $data) {
            $article ??= $request->user()->submissions()->create([
                'title' => ['az' => ''],
                'language' => $data['language'],
                'status' => 'draft',
            ]);

            $this->saveDetails($article, $data);

            if ($request->hasFile('manuscript')) {
                // One manuscript per draft: a new upload replaces the previous one.
                foreach ($article->files as $old) {
                    Storage::disk('local')->delete($old->path);
                    $old->delete();
                }
                $this->workflow->storeFile($article, $request->file('manuscript'), $request->user());
            }

            if (isset($data['authors'])) {
                $this->saveAuthors($article, $data['authors'], (int) ($data['primary'] ?? 0));
            }

            return $article;
        });

        if (! $final) {
            return redirect()->route('submit.edit', $article)->with('status', __('site.wizard.draft_saved'));
        }

        try {
            $this->workflow->submit($article);
        } catch (WorkflowException $e) {
            return redirect()->route('submit.edit', $article)->withErrors(['submit' => $e->getMessage()]);
        }

        return redirect()->route('profile.submissions.show', $article)->with('status', __('site.wizard.submitted'));
    }

    private function rules(bool $final, bool $hasFile): array
    {
        $file = ($final && ! $hasFile) ? ['required', ...self::FILE_TYPE_RULES] : ['nullable', ...self::FILE_TYPE_RULES];

        $rules = [
            'language' => ['required', 'in:az,en,ru,tr'],
            'manuscript' => $file,
            'title' => [$final ? 'required' : 'nullable', 'string', 'max:500'],
            'abstract' => $final ? ['required', 'string', 'min:50', 'max:5000'] : ['nullable', 'string', 'max:5000'],
            'keywords' => [$final ? 'required' : 'nullable', 'string', 'max:500'],
            'authors' => [$final ? 'required' : 'nullable', 'array', 'min:'.($final ? 1 : 0), 'max:20'],
            'authors.*.name' => ['required', 'string', 'max:255'],
            'authors.*.institution' => ['nullable', 'string', 'max:255'],
            'authors.*.email' => ['nullable', 'email:rfc', 'max:255'],
            'primary' => ['nullable', 'integer'],
        ];

        if ($final) {
            // The checklist and the copyright statement must be confirmed again on every final send.
            $rules['checklist'] = ['required', 'array', function ($attribute, $value, $fail) {
                // Every item must be confirmed, not just some of them.
                if (count(array_unique((array) $value)) !== count(self::CHECKLIST)) {
                    $fail(__('site.wizard.checklist_all'));
                }
            }];
            $rules['checklist.*'] = ['in:'.implode(',', self::CHECKLIST)];
            $rules['copyright'] = ['accepted'];
        }

        return $rules;
    }

    /** The form always shows a spare blank author row: drop empty rows and re-point the "primary" radio. */
    private function normaliseAuthors(Request $request): void
    {
        $rows = collect($request->input('authors', []))->filter(fn ($row) => is_array($row) && filled($row['name'] ?? null));
        $primaryKey = $request->input('primary');

        $request->merge([
            'authors' => $rows->isEmpty() ? null : $rows->values()->all(),
            'primary' => max(0, $rows->keys()->search(fn ($key) => (string) $key === (string) $primaryKey) ?: 0),
        ]);
    }

    private function saveDetails(Article $article, array $data): void
    {
        // Text is stored under the manuscript language; Turkish has no site locale, so it uses az.
        $locale = in_array($data['language'], ['az', 'en', 'ru'], true) ? $data['language'] : 'az';

        $changes = ['language' => $data['language']];
        foreach (['title', 'abstract', 'keywords'] as $field) {
            if (filled($data[$field] ?? null)) {
                $changes[$field] = [$locale => $data[$field]];
            }
        }

        $article->update($changes);
    }

    private function saveAuthors(Article $article, array $authors, int $primary): void
    {
        $article->authors()->delete();

        foreach (array_values($authors) as $i => $author) {
            $user = blank($author['email'] ?? null) ? null : User::where('email', $author['email'])->first();

            $article->authors()->create([
                'name' => $author['name'],
                'institution' => $author['institution'] ?? null,
                'email' => $author['email'] ?? null,
                'user_id' => $user?->id,
                'is_primary' => $i === $primary,
                'sort_order' => $i,
            ]);
        }
    }

    /** Only the owner may touch a draft; once it is sent the wizard is closed. */
    private function authorizeDraft(Request $request, Article $article): void
    {
        abort_unless($article->submitter_id === $request->user()->id, 404);
        abort_unless($article->isDraft(), 403, __('site.wizard.locked'));
    }
}
