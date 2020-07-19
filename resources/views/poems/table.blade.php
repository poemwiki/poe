<div class="table-responsive-sm">
    <table class="table table-striped" id="poems-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>标题</th>
                {{--<th>语言</th>--}}
                <th colspan="2">类型</th>
                <th>作者</th>
                <th>作者中文名</th>
                <th>内容长度</th>
                <th>译者</th>
                <th>朝代</th>
                <th>国家</th>
                <th>审核状态</th>
                <th colspan="3">操作</th>
            </tr>
        </thead>
        <tbody>
        @foreach($poems as $poem)
            <tr>
                <td><b>{{ $poem->id }}</b></td>
                <td>{{ $poem->title }}</td>
                {{--<td>{{ $poem->language }}</td>--}}
                <td colspan="2">{{ $poem->is_original == 0 ? '译作' : '原作' }}</td>
                <td>{{ $poem->poet }}</td>
                <td>{{ $poem->poet_cn }}</td>
                <td>{{ $poem->length }}</td>
                <td>{{ $poem->translator }}</td>
                <td>{{ $poem->dynasty }}</td>
                <td>{{ $poem->nation }}</td>
                <td>{{ $poem->need_confirm == 1 || $poem->need_confirm === null ? '待审' : '已审'  }}</td>
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
    {{ $poems->links() }}
</div>