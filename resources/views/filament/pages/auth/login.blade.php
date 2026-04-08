<div
    style="min-height: 100vh; justify-content: center; width: 100%; background-color: #FAFAFA; background-image: radial-gradient(#E5E5E5 1px, transparent 1px); background-size: 20px 20px; display: flex; flex-direction: column; align-items: center; padding: 40px 16px;">

    {{-- Logo --}}
    <div style="margin-bottom: 30px;">
        <a href="{{ route('front.index') }}">
            <img src="{{ asset('assets/images/logos/logo-black.svg') }}" alt="Tedja" style="height: 40px; width: auto;">
        </a>
    </div>

    {{-- Card --}}
    <div
        style="width: 100%; max-width: 500px; background: white; border: 1px solid #F2F2F4; border-radius: 20px; padding: 24px; box-shadow: 0px 8px 30px rgba(6,9,34,0.03);">

        {{-- Heading --}}
        <div style="text-align: center; margin-bottom: 24px;">
            <h1 style="font-weight: 700; font-size: 24px; line-height: 36px; margin-bottom: 4px;">Admin Login</h1>
            <p style="font-weight: 600; color: #8F91A2; font-size: 14px;">Sign in to access the dashboard</p>
        </div>

        {{-- Form --}}
        <x-filament-panels::form id="form" wire:submit="authenticate">
            {{ $this->form }}

            {{-- Custom Submit Button --}}
            <button type="submit"
                style="width: 100%; border-radius: 9999px; padding: 12px 20px; background-color: #CEF27F; border: none; font-weight: 600; font-size: 14px; cursor: pointer; margin-top: 14px;">
                Sign In
            </button>
        </x-filament-panels::form>
    </div>

    {{-- Back Link --}}
    <div style="margin-top: 24px;">
        <a href="{{ route('front.index') }}"
            style="display: flex; align-items: center; gap: 8px; font-size: 14px; font-weight: 600; color: #8F91A2; text-decoration: none;">
            <span style="font-size: 18px;">←</span> Back to homepage
        </a>
    </div>
</div>
