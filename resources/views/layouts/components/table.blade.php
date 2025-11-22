<div class="rounded-2xl border border-slate-200 bg-white shadow-sm mt-3">
    <table class="min-w-full text-sm overflow-x-auto" style="max-height: calc(100vh - 180px); overflow-y: auto; -webkit-overflow-scrolling: touch;" id="{{ $tableId }}">
        <thead class="text-center text-slate-500 sticky top-0 z-10 bg-white">
            <tr>
                {{ $slot }}
            </tr>
            </thead>

            <tbody id="tbody" class="divide-y divide-slate-100 bg-white"></tbody>
        </table>
    </div>
</div>

{{--@push('scripts')--}}
{{--    <script>--}}
{{--        const rect = btn.getBoundingClientRect();--}}
{{--        Object.assign(menu.style, {--}}
{{--            position: 'fixed',--}}
{{--            left: `${rect.right - menu.offsetWidth}px`,--}}
{{--            top:  `${rect.bottom + 4}px`,--}}
{{--            zIndex: 60--}}
{{--        });--}}
{{--        document.body.appendChild(menu);--}}
{{--    </script>--}}
{{--@endpush--}}
