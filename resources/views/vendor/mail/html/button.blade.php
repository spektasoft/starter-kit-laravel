@props(['url', 'color' => 'primary', 'align' => 'center'])

@php
    use Filament\Support\Facades\FilamentColor;
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
                                    style="border-radius: 8px; background-color: rgb({{ FilamentColor::getColors()[$color][500] }}); padding: 0.5rem 0.75rem;">
                                    <a href="{{ $url }}" class="button" target="_blank" rel="noopener"
                                        style="background-color: transparent; display: inline-block;">{!! $slot !!}</a>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
