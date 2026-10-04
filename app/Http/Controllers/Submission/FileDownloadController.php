<?php

namespace App\Http\Controllers\Submission;

use App\Http\Controllers\Controller;
use App\Models\ArticleFile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class FileDownloadController extends Controller
{
    public function __invoke(Request $request, ArticleFile $file)
    {
        $user = $request->user();
        $article = $file->article;

        $isStaff = (bool) $user->is_admin;
        $isOwner = $article->submitter_id === $user->id;
        $isReviewer = $article->reviews()
            ->where('reviewer_id', $user->id)
            ->whereIn('status', ['accepted', 'completed'])
            ->exists();

        abort_unless($isStaff || $isOwner || $isReviewer, 404);
        abort_unless(Storage::disk('local')->exists($file->path), 404);

        // Reviewers get a neutral file name: the original can contain the author name.
        $name = ($isReviewer && ! $isStaff && ! $isOwner)
            ? 'manuscript-'.$file->id.'.'.$file->extension
            : $file->original_name;

        return Storage::disk('local')->download($file->path, $name);
    }
}
