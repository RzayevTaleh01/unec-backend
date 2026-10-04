<?php

namespace App\Filament\Pages;

use App\Models\Setting;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

/**
 * Editable site-wide texts (home hero, footer quote, "Open Journal Systems" link).
 * Empty values fall back to the defaults in lang/*\/site.php.
 */
class SiteSettings extends Page implements Forms\Contracts\HasForms
{
    use Forms\Concerns\InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static ?string $navigationGroup = 'Ayarlar';

    protected static ?string $navigationLabel = 'Sayt ayarları';

    protected static ?string $title = 'Sayt ayarları';

    protected static string $view = 'filament.pages.site-settings';

    public static function canAccess(): bool
    {
        return (bool) auth()->user()?->is_admin;
    }

    private const LOCALES = ['az' => 'Azərbaycan', 'en' => 'English', 'ru' => 'Русский'];

    /** @var array<string, mixed> */
    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'hero_title' => Setting::find('hero_title')?->getTranslations('value') ?? [],
            'hero_text' => Setting::find('hero_text')?->getTranslations('value') ?? [],
            'footer_quote' => Setting::find('footer_quote')?->getTranslations('value') ?? [],
            'footer_quote_author' => Setting::find('footer_quote_author')?->getTranslations('value') ?? [],
            'systems_url' => Setting::get('systems_url'),
            'map_embed_url' => Setting::get('map_embed_url'),
        ]);
    }

    public function form(Form $form): Form
    {
        $tabs = [];
        foreach (self::LOCALES as $code => $label) {
            $tabs[] = Forms\Components\Tabs\Tab::make($label)->schema([
                Forms\Components\TextInput::make("hero_title.{$code}")->label('Ana səhifə: başlıq')->maxLength(255),
                Forms\Components\Textarea::make("hero_text.{$code}")->label('Ana səhifə: təsvir')->rows(3)->maxLength(600),
                Forms\Components\TextInput::make("footer_quote.{$code}")->label('Footer: sitat')->maxLength(255),
                Forms\Components\TextInput::make("footer_quote_author.{$code}")->label('Footer: sitatın müəllifi')->maxLength(255),
            ]);
        }

        return $form
            ->statePath('data')
            ->schema([
                Forms\Components\Tabs::make('locales')->tabs($tabs),
                Forms\Components\Section::make('Keçidlər')->schema([
                    Forms\Components\TextInput::make('systems_url')
                        ->label('"Açıq Jurnal sistemləri" xarici linki')
                        ->url()
                        ->helperText('Boş buraxılsa, menyu daxili səhifəyə yönləndirir.'),
                ]),
                Forms\Components\Section::make('Əlaqə səhifəsi: xəritə')->schema([
                    Forms\Components\Textarea::make('map_embed_url')
                        ->label('Xəritə linki və ya iframe kodu')
                        ->rows(3)
                        ->helperText('Google Maps → Paylaş → Xəritəni yerləşdir bölməsindəki iframe kodunu (və ya OpenStreetMap/Yandex embed linkini) yapışdırın. Boş buraxılsa, standart UNEC xəritəsi göstərilir.')
                        ->rules([fn () => function (string $attribute, $value, \Closure $fail) {
                            if (filled($value) && ! self::extractMapUrl($value)) {
                                $fail('Yalnız Google Maps, OpenStreetMap və ya Yandex xəritə linki qəbul olunur (https://...).');
                            }
                        }]),
                ]),
            ]);
    }

    public function save(): void
    {
        $data = $this->form->getState();

        foreach (['hero_title', 'hero_text', 'footer_quote', 'footer_quote_author'] as $key) {
            $values = array_filter($data[$key] ?? [], fn ($value) => filled($value));
            $setting = Setting::firstOrNew(['key' => $key]);
            $setting->value = $values;
            $setting->save();
        }

        Setting::updateOrCreate(['key' => 'map_embed_url'], ['value' => array_filter(['az' => self::extractMapUrl($data['map_embed_url'] ?? '')])]);

        Setting::updateOrCreate(['key' => 'systems_url'], ['value' => array_filter(['az' => $data['systems_url'] ?? null])]);

        Notification::make()->title('Ayarlar yadda saxlanıldı')->success()->send();
    }

    /** Accepts a bare URL or a pasted <iframe> snippet; only known map providers over https are allowed. */
    public static function extractMapUrl(?string $input): ?string
    {
        $input = trim((string) $input);
        if ($input === '') {
            return null;
        }
        if (preg_match('/src\s*=\s*(["\'])(.+?)\1/i', $input, $m)) {
            $input = html_entity_decode($m[2]);
        }
        $parts = parse_url($input);
        $host = strtolower($parts['host'] ?? '');
        $allowed = ['google.com', 'www.google.com', 'maps.google.com', 'openstreetmap.org', 'www.openstreetmap.org', 'yandex.com', 'yandex.az', 'yandex.ru'];

        return ($parts['scheme'] ?? '') === 'https' && in_array($host, $allowed, true) ? $input : null;
    }
}
