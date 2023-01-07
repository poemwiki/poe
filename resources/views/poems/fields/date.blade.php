@if($poem->year or $poem->month)
  @if($poem->year && $poem->month && $poem->date)
    <dd itemprop="dateCreated" class="poem-time">{{$poem->year}}.{{$poem->month}}.{{$poem->date}}</dd>
  @elseif($poem->year && $poem->month)
    <dd itemprop="dateCreated" class="poem-time">{{$poem->year}}.{{$poem->month}}</dd>
  @elseif($poem->month && $poem->date)
    <dd itemprop="dateCreated" class="poem-time">{{$poem->month}}.{{$poem->date}}</dd>
  @elseif($poem->year)
    <dd itemprop="dateCreated" class="poem-time">{{$poem->year}}</dd>
  @endif
@endif