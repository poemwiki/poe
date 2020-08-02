@extends('layouts.fe')

@section('content')
     <div class="container-fluid">
          <div class="animated fadeIn">
                 <div class="row">
                     <div class="col-lg-12">
                         <div class="card">
                             <div class="card-body">
                                 @include('poems.show_fields')
                             </div>
                         </div>
                     </div>
                 </div>
          </div>
    </div>
@endsection
