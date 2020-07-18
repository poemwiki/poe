<div class="table-responsive-sm">
    <table class="table table-striped" id="poems-table">
        <thead>
            <tr>
                <th>Title</th>
        <th>Language</th>
        <th>Is Original</th>
        <th>Poet</th>
        <th>Poet Cn</th>
        <th>Bedtime Post Id</th>
        <th>Bedtime Post Title</th>
        <th>Poem</th>
        <th>Length</th>
        <th>Translator</th>
        <th>From</th>
        <th>Year</th>
        <th>Month</th>
        <th>Date</th>
        <th>Dynasty</th>
        <th>Nation</th>
        <th>Need Confirm</th>
        <th>Is Lock</th>
                <th colspan="3">Action</th>
            </tr>
        </thead>
        <tbody>
        @foreach($poems as $poem)
            <tr>
                <td>{{ $poem->title }}</td>
            <td>{{ $poem->language }}</td>
            <td>{{ $poem->is_original }}</td>
            <td>{{ $poem->poet }}</td>
            <td>{{ $poem->poet_cn }}</td>
            <td>{{ $poem->bedtime_post_id }}</td>
            <td>{{ $poem->bedtime_post_title }}</td>
            <td>{{ $poem->poem }}</td>
            <td>{{ $poem->length }}</td>
            <td>{{ $poem->translator }}</td>
            <td>{{ $poem->from }}</td>
            <td>{{ $poem->year }}</td>
            <td>{{ $poem->month }}</td>
            <td>{{ $poem->date }}</td>
            <td>{{ $poem->dynasty }}</td>
            <td>{{ $poem->nation }}</td>
            <td>{{ $poem->need_confirm }}</td>
            <td>{{ $poem->is_lock }}</td>
                <td>
                    {!! Form::open(['route' => ['poems.destroy', $poem->id], 'method' => 'delete']) !!}
                    <div class='btn-group'>
                        <a href="{{ route('poems.show', [$poem->id]) }}" class='btn btn-ghost-success'><i class="fa fa-eye"></i></a>
                        <a href="{{ route('poems.edit', [$poem->id]) }}" class='btn btn-ghost-info'><i class="fa fa-edit"></i></a>
                        {!! Form::button('<i class="fa fa-trash"></i>', ['type' => 'submit', 'class' => 'btn btn-ghost-danger', 'onclick' => "return confirm('Are you sure?')"]) !!}
                    </div>
                    {!! Form::close() !!}
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>