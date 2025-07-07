@props(['url', 'color' => 'primary', 'align' => 'center'])

@php
    use Filament\Support\Facades\FilamentColor;
    $colors = FilamentColor::getColors();
    $buttonColor = array_key_exists($color, $colors) ? $colors[$color][500] : $colors['primary'][500];
@endphp

<table class="action" align="{{ $align }}" width="100%" cellpadding="0" cellspacing="0" role="presentation">
    <tr>
        <td align="{{ $align }}">
            <table width="100%" border="0" cellpadding="0" cellspacing="0" role="presentation">
                <tr>
                    <td align="{{ $align }}">
                        <table border="0" cellpadding="0" cellspacing="0" role="presentation">
                            <tr>
                                <td
                                    style="background-color: rgb({{ $buttonColor }}); border-radius: 8px; padding: 0.5rem 0.75rem;">
                                    <a href="{{ $url }}" target="_blank" rel="noopener"
                                        style="color: #fff; overflow: hidden; text-decoration: none;">{!! $slot !!}</a>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
