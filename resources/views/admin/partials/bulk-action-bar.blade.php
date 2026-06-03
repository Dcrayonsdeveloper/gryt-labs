{{-- Bulk Action Bar — include inside x-data that has selected[] --}}
{{-- Usage: @include('admin.partials.bulk-action-bar', ['route' => route('admin.xxx.bulk-action'), 'actions' => ['delete' => 'Delete']]) --}}
<div x-show="selected.length > 0" x-cloak
     style="padding:8px 12px;background:#f3f3f3;border-bottom:1px solid #e1e1e1" class="flex items-center justify-between gap-3">
    <span style="font-size:13px;font-weight:500;color:#323232">
        <span x-text="selected.length"></span> selected
    </span>
    <form method="POST" action="{{ $route }}"
          x-ref="bulkForm"
          @submit.prevent="
              const action = $refs.bulkAction.value;
              if (!action) { alert('Please select an action'); return; }
              if (!confirm('Are you sure you want to ' + action + ' ' + selected.length + ' item(s)?')) return;
              $refs.bulkIds.value = JSON.stringify(selected);
              $el.submit();
          ">
        @csrf
        <input type="hidden" name="ids" x-ref="bulkIds">
        <div class="flex items-center gap-2">
            <select name="action" x-ref="bulkAction"
                    style="padding:5px 8px;font-size:12px;border:1px solid #c9c9c9;border-radius:6px;outline:none;background:#fff">
                <option value="">Bulk action</option>
                @foreach($actions as $value => $label)
                    <option value="{{ $value }}">{{ $label }}</option>
                @endforeach
            </select>
            <button type="submit"
                    style="padding:5px 10px;font-size:12px;font-weight:500;background:#fff;border:1px solid #c9c9c9;color:#323232;border-radius:6px;cursor:pointer"
                    onmouseover="this.style.background='#f7f7f7'" onmouseout="this.style.background='#fff'">
                Apply
            </button>
        </div>
    </form>
</div>
