@props(['timeout' => 4000])

@if (session('success') || session('error') || session('warning') || session('info'))
  <div x-data="{ show:true }" x-show="show"
       x-init="setTimeout(()=>show=false, {{ (int)$timeout }})"
       class="mb-4">
    @if (session('success'))
      <div class="rounded border border-green-200 bg-green-50 px-4 py-3 text-green-800" role="status" aria-live="polite">
        {{ session('success') }}
      </div>
    @endif
    @if (session('error'))
      <div class="rounded border border-red-200 bg-red-50 px-4 py-3 text-red-800" role="alert" aria-live="assertive">
        {{ session('error') }}
      </div>
    @endif
    @if (session('warning'))
      <div class="rounded border border-yellow-200 bg-yellow-50 px-4 py-3 text-yellow-800" role="status" aria-live="polite">
        {{ session('warning') }}
      </div>
    @endif
    @if (session('info'))
      <div class="rounded border border-blue-200 bg-blue-50 px-4 py-3 text-blue-800" role="status" aria-live="polite">
        {{ session('info') }}
      </div>
    @endif
  </div>
@endif
