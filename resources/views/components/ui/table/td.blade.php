{{-- overflow-hidden wajib agar truncate bekerja di dalam table-fixed --}}
<td {{ $attributes->class('px-4 py-4 align-middle overflow-hidden') }}>{{ $slot }}</td>