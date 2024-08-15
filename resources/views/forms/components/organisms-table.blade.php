

<div>
    <table class="table-auto w-full">
        <thead>
            <tr>
                @foreach(array_keys($getTableData($getRecord()->name)[0] ?? []) as $header)
                    <th class="px-4 py-2">{{ $header }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @foreach($getTableData($getRecord()->name) as $row)
                <tr>
                    @foreach($row as $value)
                        <td class="border px-4 py-2">{{ $value }}</td>
                    @endforeach
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
