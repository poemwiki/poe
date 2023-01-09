@if($poem->year or $poem->month)
  @if($poem->year && $poem->month && $poem->date)
    <dd itemprop="dateCreated" class="{{$class}}">{{$poem->year}}.{{$poem->month}}.{{$poem->date}}</dd>
  @elseif($poem->year && $poem->month)
    <dd itemprop="dateCreated" class="{{$class}}">{{$poem->year}}.{{$poem->month}}</dd>
  @elseif($poem->month && $poem->date)
    <dd itemprop="dateCreated" class="{{$class}}">{{$poem->month}}.{{$poem->date}}</dd>
  @elseif($poem->year)
    <dd itemprop="dateCreated" class="{{$class}}">{{$poem->year}}</dd>
  @endif
@endif