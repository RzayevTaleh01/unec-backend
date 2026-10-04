<?php

namespace Database\Seeders;

use App\Models\Announcement;
use App\Models\Article;
use App\Models\Contact;
use App\Models\EditorialMember;
use App\Models\Issue;
use App\Models\Journal;
use App\Models\Page;
use App\Models\PageSection;
use Illuminate\Database\Seeder;

/**
 * Loads the content that was hard-coded in the static HTML pages.
 */
class ContentSeeder extends Seeder
{
    private const DOI = 'https://doi.org/10.30546/0a2ted920';

    public function run(): void
    {
        $this->journals();
        $this->pages();
        $this->issuesAndArticles();
        $this->announcements();
        $this->editorialAndContacts();
    }

    private function journals(): void
    {
        $journals = [
            ['unec-student-research', 'UNEC Tələbə Tədqiqatları Jurnalı', true],
            ['economics-management-advances', 'Journal of Economics and Management Advances', false],
            ['computer-science-digital-technologies', 'Journal of Computer Science and Digital Technologies', false],
            ['unec-scientific-news', 'AZƏRBAYCAN DÖVLƏT İQTİSAD UNİVERSİTETİNİN ELMİ XƏBƏRLƏRİ', false],
            ['actual-problems-of-design', 'Dizaynın aktual problemləri', false],
        ];

        foreach ($journals as $i => [$slug, $name, $primary]) {
            Journal::updateOrCreate(['slug' => $slug], ['name' => ['az' => $name, 'en' => $name, 'ru' => $name], 'is_primary' => $primary, 'sort_order' => $i]);
        }
    }

    private function pages(): void
    {
        $data = json_decode(file_get_contents(__DIR__.'/data/pages.json'), true, 512, JSON_THROW_ON_ERROR);

        $sections = [
            'about' => ['Jurnal haqqında', 'About the journal', 'О журнале'],
            'submission' => ['Məqalə təqdimatı', 'Article submission', 'Подача статьи'],
            'information' => ['Məlumat', 'Information', 'Информация'],
        ];

        $order = 0;
        foreach ($sections as $key => [$az, $en, $ru]) {
            $section = PageSection::updateOrCreate(['key' => $key], ['title' => compact('az', 'en', 'ru'), 'sort_order' => ++$order]);

            foreach ($data[$key] as $item) {
                Page::updateOrCreate(
                    ['page_section_id' => $section->id, 'slug' => $item['slug']],
                    ['title' => ['az' => $item['title']], 'body' => ['az' => $this->fixLinks($item['body'])], 'sort_order' => $item['sort'], 'is_published' => true],
                );
            }
        }

        $standalone = [
            'privacy' => ['Məxfilik siyasəti', 'Bu jurnal saytındakı adlar və email adresləri bu jurnalın qeyd olunan məqsədləri üçün istifadə ediləcəkdir. Başqa məqsədlər və ya başqa bölümlər üçün istifadə edilməyəcəkdir.'],
            'terms' => ['Qaydalar və şərtlər', 'Bu səhifənin məzmunu admin paneldən redaktə edilə bilər.'],
            'open-journal-systems' => ['Açıq Jurnal sistemləri', 'Açıq Jurnal sistemləri haqqında məlumat tezliklə əlavə ediləcək.'],
        ];

        foreach ($standalone as $slug => [$title, $body]) {
            Page::updateOrCreate(
                ['page_section_id' => null, 'slug' => $slug],
                ['title' => ['az' => $title], 'body' => ['az' => '<p>'.$body.'</p>'], 'is_published' => true],
            );
        }
    }

    /** The static HTML had empty "#" links; point them at the real routes. */
    private function fixLinks(string $html): string
    {
        return strtr($html, [
            '<a href="#">Qeydiyyat</a>' => '<a href="'.route('register', [], false).'">Qeydiyyat</a>',
            '<a href="#">təhlükəsizlik qaydalarına baxın</a>' => '<a href="'.route('privacy', [], false).'">təhlükəsizlik qaydalarına baxın</a>',
            '<a href="">Jurnal Haqqında</a>' => '<a href="'.route('pages.first', 'about', false).'">Jurnal Haqqında</a>',
            '<a href="">Müəllif Bələdçisi</a>' => '<a href="'.route('pages.show', ['submission', 'author-guide'], false).'">Müəllif Bələdçisi</a>',
            '<a href="">register</a>' => '<a href="'.route('register', [], false).'">qeydiyyatdan keçərək</a>',
            '<a href="">Üzv girişi</a>' => '<a href="'.route('login', [], false).'">Üzv girişi</a>',
            '<a href="">Public Knowledge Project</a>' => '<a href="https://pkp.sfu.ca" target="_blank" rel="noopener">Public Knowledge Project</a>',
            '<a href="#">Məqalə Göndər</a>' => '<a href="'.route('login', [], false).'">Məqalə Göndər</a>',
        ]);
    }

    private function issuesAndArticles(): void
    {
        $description = 'UNEC Tələbə Tədqiqatları Jurnalının 2026-cı il, 3-cü cild, 1-ci nömrəsi müxtəlif elm sahələrini əhatə edən aktual və multidissiplinar tədqiqatların nəticələrini özündə ehtiva edir. Buraxılışda iqtisadiyyat, maliyyə, biznes, coğrafiya, informasiya texnologiyaları, süni intellekt, təhsil, sosial elmlər və mühəndislik istiqamətləri üzrə aparılmış elmi araşdırmalar müasir nəzəri yanaşmalar və praktik təhlillər əsasında təqdim olunmuşdur. Nəşrdə yer alan məqalələr elmi metodologiyaya uyğun hazırlanaraq aktual problemlərin elmi əsaslarla qiymətləndirilməsinə, innovativ həll istiqamətlərinin müəyyənləşdirilməsinə və elmi biliklər bazasının zənginləşdirilməsinə xidmət edir.';

        $issues = [
            [3, 1, 2026, '2026-02-25', 'archive-1.png', self::DOI],
            [2, 2, 2025, '2025-12-20', 'archive-2.png', null],
            [2, 1, 2025, '2025-06-05', 'archive-3.png', null],
            [1, 2, 2024, '2024-12-18', 'archive-4.png', null],
            [1, 1, 2024, '2024-06-10', 'archive-5.png', null],
        ];

        $created = [];
        foreach ($issues as [$volume, $number, $year, $date, $cover, $doi]) {
            $created["$volume.$number.$year"] = Issue::updateOrCreate(
                compact('volume', 'number', 'year'),
                [
                    'description' => ['az' => $description],
                    'cover' => 'assets/images/archive/'.$cover,
                    'doi' => $doi,
                    'pdf' => $doi,
                    'published_at' => $date,
                    'is_published' => true,
                ],
            );
        }

        $articles = [
            ['3.1.2026', 'MUHASİBAT VƏ DENETİMDE BİLİŞİM TEXNOLOGİLERİ KULLANIMI: TÜRKİYE–AZERBAYCAN KARŞILAŞTIRMASI', 'Asude Nuriyeva', '2026-02-25', 'bilişim teknolojileri, bulut tabanlı yazılımlar, dijital dönüşüm, e-fatura, veri analitiği',
                'Muhasebe ve denetim süreçlerinde bilişim teknolojilerinin entegrasyonu, finansal işlemlerin doğruluğunu, hızını ve şeffaflığını yükselterek ekonomik verimliliği güçlendirir. Gelişmekte olan ekonomilerde bu teknolojiler, küresel rekabet avantajı ve yasal uyum açısından vazgeçilmezdir. Türkiye ve Azerbaycan, ekonomik yapı ve teknolojik altyapı bakımından farklılıklar gösterse de, her iki ülke dijital dönüşümde kayda değer ilerlemeler kaydetmektedir. Ancak KOBİ’lerin adaptasyon zorlukları ile siber güvenlik riskleri, uygulamaların etkinliğini kısıtlamaktadır. Bulut bilişim, yapay zeka ve veri analitiği gibi yenilikler muhasebe işlemlerini otomatikleştirerek hata oranlarını düşürmekte ve gerçek zamanlı veri erişimini mümkün kılmaktadır.'],
            ['3.1.2026', 'POST-MÜNAQİŞƏ ƏRAZİLƏRİNDƏ VERGİ GÜZƏŞTLƏRİNİN İNVESTİSİYA CƏLBEDİCİLİYİNƏ TƏSİRİ: QARABAĞ VƏ ŞƏRQİ ZƏNGƏZUR', 'Fəridə Baxşaliyeva', '2026-02-25', 'vergi güzəştləri, investisiya, Qarabağ, Şərqi Zəngəzur', null],
            ['2.2.2025', 'İNOVATİF EKONOMİYƏ GEÇİŞTE KOBİ’LƏRİN FİNANSMAN GÜCLÜKLƏRİ VƏ FINTECH TƏMƏLİ ÇÖZÜM STRATEJİLƏRİ (AZERBAYCAN ÖRNEĞİ)', 'Asude Nuriyeva', '2025-05-06', 'KOBİ, fintech, innovativ iqtisadiyyat', null],
            ['1.1.2024', 'Rəqəmsal iqtisadiyyatın regional inkişafa təsiri', 'Fəridə Baxşaliyeva', '2024-06-10', 'rəqəmsal iqtisadiyyat, regional inkişaf', null],
        ];

        foreach ($articles as [$issueKey, $title, $author, $date, $keywords, $abstract]) {
            if (Article::where('title->az', $title)->exists()) {
                continue;
            }

            $article = Article::create([
                'issue_id' => $created[$issueKey]->id,
                'title' => ['az' => $title],
                'abstract' => $abstract ? ['az' => $abstract] : null,
                'keywords' => ['az' => $keywords],
                'doi' => self::DOI,
                'pdf' => self::DOI,
                'status' => 'published',
                'published_at' => $date,
            ]);

            $article->authors()->create(['name' => $author, 'is_primary' => true, 'sort_order' => 0]);
        }
    }

    private function announcements(): void
    {
        $body = '<p>Konfrans qlobal istiləşmə, iqlim dəyişiklikləri, enerji, ətraf mühit və dayanıqlı inkişaf sahələrində fəaliyyət göstərən alimləri, tədqiqatçıları və mütəxəssisləri bir araya gətirəcək.</p><p>Multidisiplinar beynəlxalq elmi platforma olan GCW-2026 qlobal istiləşmənin təsirləri, bərpaolunan enerji, hidrogen texnologiyaları, enerji siyasəti, karbon emissiyalarının azaldılması, enerji səmərəliliyi, ekosistemlərin qorunması, süni intellektin enerji sektorunda tətbiqi və digər aktual istiqamətlər üzrə elmi müzakirələrin aparılmasına imkan yaradacaq.</p><p>Konfransa qəbul edilərək təqdim olunan məqalələr konfrans materiallarında dərc olunacaq. Seçilmiş yüksəkkeyfiyyətli elmi işlərin genişləndirilmiş versiyalarının isə beynəlxalq elmi jurnalların xüsusi buraxılışlarında nəşri nəzərdə tutulur.</p>';

        $items = [
            ['aztu-14-qlobal-istilesme-konfransi', 'AzTU-da 14-cü Qlobal İstiləşmə üzrə beynəlxalq konfrans keçiriləcək', 'announcement-1.png'],
            ['unec-yeni-tehsil-proqramlari', 'UNEC-də ən son təhsil proqramları və yeni imkanlar təqdim edildi', 'announcement-2.png'],
            ['aztu-qlobal-istilesme-konfransi-2', 'AzTU-da 14-cü Qlobal İstiləşmə üzrə beynəlxalq konfrans keçiriləcək', 'announcement-3.png'],
            ['unec-udip-proqrami', 'UNEC-də 4 ilə 2 diplom qazanmaq imkanı – UDİP proqramı', 'announcement-4.png'],
        ];

        foreach ($items as [$slug, $title, $image]) {
            Announcement::updateOrCreate(['slug' => $slug], [
                'title' => ['az' => $title],
                'body' => ['az' => $body],
                'image' => 'assets/images/announcements/'.$image,
                'published_at' => '2026-05-06',
                'views_count' => 125,
                'is_published' => true,
            ]);
        }
    }

    private function editorialAndContacts(): void
    {
        $members = [
            ['Səma Əliyeva Mamed', ['az' => 'Baş redaktor', 'en' => 'Editor-in-Chief', 'ru' => 'Главный редактор'], '1.png'],
            ['Emin Qəribli Adil', ['az' => 'Elmi redaktor', 'en' => 'Scientific Editor', 'ru' => 'Научный редактор'], '2.png'],
            ['Yusif Musayev Ehtiram', ['az' => 'İdarəedici redaktor', 'en' => 'Managing Editor', 'ru' => 'Управляющий редактор'], '3.png'],
        ];

        foreach ($members as $i => [$name, $role, $photo]) {
            EditorialMember::updateOrCreate(['name' => $name], ['role' => $role, 'photo' => 'assets/images/announcements/'.$photo, 'sort_order' => $i, 'is_active' => true]);
        }

        $organization = ['az' => "Azərbaycan Dövlət İqtisad Universiteti\n(UNEC)", 'en' => "Azerbaijan State University of Economics\n(UNEC)", 'ru' => "Азербайджанский государственный экономический университет\n(UNEC)"];

        Contact::updateOrCreate(['email' => 'ssjournal@unec.edu.az'], [
            'title' => ['az' => 'Əsas əlaqə', 'en' => 'Main contact', 'ru' => 'Основной контакт'],
            'name' => 'Səma Əliyeva Mamed',
            'organization' => $organization,
            'phone' => '+994 12 492 59 03 (1044)',
            'sort_order' => 0,
        ]);

        Contact::updateOrCreate(['email' => 'yusif.musayev@unec.edu.az'], [
            'title' => ['az' => 'Dəstək əlaqə', 'en' => 'Support contact', 'ru' => 'Контакт поддержки'],
            'name' => 'Yusif Musayev',
            'organization' => $organization,
            'phone' => '+994 12 492 59 03 (1044)',
            'sort_order' => 1,
        ]);
    }
}
