# UNEC Tələbə Tədqiqatları Jurnalı: Laravel backend

Laravel 12 + Filament 3 (admin panel) + Blade. Kökdəki statik HTML səhifələri bu layihədə
server-rendered Blade şablonlarına çevrilib (`resources/views`), dizayn (`public/assets`) dəyişməyib.

## Quraşdırma

```bash
composer install --ignore-platform-req=ext-zip   # ext-zip yoxdursa; yalnız Filament Excel export-u üçün lazımdır
cp .env.example .env
php artisan key:generate
touch database/database.sqlite                   # dev üçün SQLite; prod-da MySQL üçün .env-də DB_* təyin edin
php artisan migrate --seed                       # statik HTML-dəki məzmunu bazaya yükləyir
php artisan storage:link
php artisan app:create-admin ad@unec.edu.az      # admin yaradır (şifrə soruşulur)
php artisan serve
```

- Sayt: `http://127.0.0.1:8000`
- Admin panel: `http://127.0.0.1:8000/admin` (yalnız `is_admin` istifadəçilər)

## Admin paneldən idarə olunanlar

| Bölmə | Nəyi idarə edir |
|---|---|
| Səhifələr | Əvvəlki **bütün tablar** (Jurnal haqqında, Məqalə təqdimatı, Məlumat) və Məxfilik/Qaydalar. Hər biri ayrı URL: `/about/purpose`, `/submission/ethics` ... |
| Səhifə bölmələri | Yeni bölmə əlavə etmək (`/açar/səhifə`); bölmənin səhifələri tab menyusunda avtomatik görünür |
| Elanlar | Mətn, şəkil, dərc tarixi (gələcək tarix = planlı dərc), baxış sayğacı |
| Buraxılışlar (Arxiv) | Cild/nömrə/il, cover, PDF, DOI, buraxılışın məqalələri |
| Məqalələr | Müəlliflər (repeater), xülasə, açar sözlər, status, PDF, DOI |
| Redaksiya heyəti, Əlaqə kartları, Digər jurnallar | Sıralanan (sürüşdür-burax) siyahılar |
| İstifadəçilər | Rollar və admin girişi |
| Sayt ayarları | Ana səhifə başlığı/təsviri, footer sitatı, "Açıq Jurnal sistemləri" linki |

Məzmun AZ / EN / RU dillərində girilir (yuxarı sağdakı dil seçicisi). Tərcümə olunmayan sahə Azərbaycan dilinə düşür.

## Ictimai sayt

`/`, `/archive`, `/archive/{id}`, `/articles/{id}`, `/announcements`, `/announcements/{slug}`, `/search`,
`/editorial-board`, `/contact`, `/about/*`, `/submission/*`, `/information/*`, `/privacy`, `/terms`, `/open-journal-systems`.

Auth: `/login`, `/register/personal` → `/register/account` → `/register/success`, `/forgot-password`, `/reset-password/{token}`.

Profil (hər tab ayrı səhifə): `/profile/identity`, `contact`, `roles` (+ `roles/journals`), `public`, `password`,
`notifications`, `api-key`, `submissions`, `reviews`.

## API

Profildəki "API açarı" səhifəsində yaradılan açarla (Bearer token): `GET /api/v1/me`, `/api/v1/issues`, `/api/v1/articles`.

## Testlər

```bash
php artisan test
```

## Məqalə təqdimatı və rəylənmə

**Müəllif** (`/submit`, bir səhifə, addımlar JavaScript ilə dəyişir): başlanğıc (nəzarət siyahısı) → fayl → məlumatlar → müəlliflər → təsdiq. Hər addımda "Qaralama kimi saxla" var, qaralama `/submit/{id}`-də davam etdirilir.
Göndərildikdən sonra `/profile/submissions/{id}` səhifəsində status, redaksiya qərarları və rəyçi qeydləri görünür;
düzəliş tələb olunanda yeni variant yüklənir.

**Redaktor** (`/admin` → Məqalələr → məqaləni aç): "Rəyçi təyin et" və "Qərar ver" düymələri, altda yüklənmiş fayllar,
rəylər və qərar tarixçəsi. Naviqasiyada yeni təqdimatların sayı görünür. Status yalnız bu düymələrlə dəyişir.

**Rəyçi** (`/profile/reviews`): dəvəti qəbul/imtina → fayl yüklənir → tövsiyə və qeydlər göndərilir. Rəylənmə anonimdir
(müəllif adları gizlədilir, fayl neytral adla yüklənir; müəllif rəyçini görmür).

Status axını: `draft → submitted → in_review → accepted | revisions | rejected`, `revisions → submitted`, `accepted → published`.
Bütün keçidlər `app/Services/SubmissionWorkflow.php`-dədir. Bildirişlər (qutu `/profile/inbox` + email) istifadəçinin
`profile/notifications` seçimlərinə görə göndərilir. Fayllar `storage/app/private/articles`-də saxlanır (ictimai deyil),
yalnız müəllif, təyin olunmuş rəyçi və admin endirə bilər.

## Hələ edilməyənlər

- Email doğrulaması (`MustVerifyEmail`) qoşulmayıb.
- Bildirişlər yalnız təqdimat/rəylənmə hadisələri üçün göndərilir (yeni elan/nömrə və həftəlik xülasə üçün yox).
- Rəyçi faylın **daxilində** müəllif adı varsa, onu sistem silmir; müəllif bunu yükləməzdən əvvəl çıxarmalıdır.
- Mail: `.env`-də `MAIL_MAILER=log` olduğu müddətcə mail-lər `storage/logs`-a yazılır; real SMTP qoşulmalıdır.
- Rəylənmə və təqdimat üçün queue worker istifadə olunmur (bildirişlər sinxron göndərilir).
