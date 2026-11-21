{{-- Tanpa overflow-x-auto: tidak ada scrollbar & dropdown tidak terpotong --}}
<div {{ $attributes->class('hidden lg:block') }}>
    <table class="w-full table-fixed text-left">
        {{ $slot }}
    </table>
</div>