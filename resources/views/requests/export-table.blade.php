<table>
    <thead>
        <tr>
            @foreach($header as $item)
            <th>{{ Str::headline($item) }}</th>
            @endforeach
        </tr>
    </thead>
    <tbody>
        @foreach($items as $item)
        <tr>
            @foreach($item as $key)
            <td>{{ $key }}</td>
            @endforeach
        </tr>
        @endforeach
    </tbody>
</table>
