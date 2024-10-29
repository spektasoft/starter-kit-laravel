<div>
    <div class="max-w-xl text-sm text-gray-600 dark:text-gray-400">
        {{ __('If necessary, you may log out of all of your other browser sessions across all of your devices. Some of your recent sessions are listed below; however, this list may not be exhaustive. If you feel your account has been compromised, you should also update your password.') }}
    </div>

    @if (count($this->sessions) > 0)
        <div class="mt-5 space-y-6">
            <!-- Other Browser Sessions -->
            @foreach ($this->sessions as $session)
                <div class="flex items-center">
                    <div>
                        @if ($session->agent->isDesktop())
                            @svg('heroicon-o-computer-desktop', 'w-8 h-8 text-gray-500')
                        @else
                            @svg('heroicon-o-device-phone-mobile', 'w-8 h-8 text-gray-500')
                        @endif
                    </div>

                    <div class="ms-3">
                        <div class="text-sm text-gray-600 dark:text-gray-400">
                            {{ $session->agent->platform() ? $session->agent->platform() : __('Unknown') }} -
                            {{ $session->agent->browser() ? $session->agent->browser() : __('Unknown') }}
                        </div>

                        <div>
                            <div class="text-xs text-gray-500">
                                {{ $session->ip_address }},

                                @if ($session->is_current_device)
                                    <span class="font-semibold text-green-500">{{ __('This device') }}</span>
                                @else
                                    {{ __('Last active') }} {{ $session->last_active }}
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
