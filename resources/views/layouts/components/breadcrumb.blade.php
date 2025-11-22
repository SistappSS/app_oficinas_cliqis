<h1>{{$title}}</h1>
<nav class="breadcrumb-container d-none d-sm-block d-lg-inline-block" aria-label="breadcrumb">
    <ol class="breadcrumb pt-0">
        <li class="breadcrumb-item">
            <a href="{{route('dashboard')}}">Dashboard</a>
        </li>
        <li class="breadcrumb-item active" aria-current="page">{{$current}}</li>
    </ol>
</nav>
