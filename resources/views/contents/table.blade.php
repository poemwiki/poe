<div class="table-responsive-sm">
    <table class="table table-striped" id="contents-table">
        <thead>
            <tr>
                <th>Hash</th>
        <th>New Hash</th>
        <th>Type</th>
        <th>Entry Id</th>
        <th>Content</th>
                <th colspan="3">Action</th>
            </tr>
        </thead>
        <tbody>
        @foreach($contents as $content)
            <tr>
                <td>{{ $content->hash }}</td>
            <td>{{ $content->new_hash }}</td>
            <td>{{ $content->type }}</td>
            <td>{{ $content->entry_id }}</td>
            <td>{{ $content->content }}</td>
                <td>
                    {!! Form::open(['route' => ['contents.destroy', $content->id], 'method' => 'delete']) !!}
                    <div class='btn-group'>
                        <a href="{{ route('contents.show', [$content->id]) }}" class='btn btn-ghost-success'><i class="fa fa-eye"></i></a>
                        <a href="{{ route('contents.edit', [$content->id]) }}" class='btn btn-ghost-info'><i class="fa fa-edit"></i></a>
                        {!! Form::button('<i class="fa fa-trash"></i>', ['type' => 'submit', 'class' => 'btn btn-ghost-danger', 'onclick' => "return confirm('Are you sure?')"]) !!}
                    </div>
                    {!! Form::close() !!}
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>