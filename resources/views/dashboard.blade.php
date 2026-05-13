@extends('layouts.app')

@section('content')

<div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-6">

    <div class="card p-6 card-hover">

        <div class="flex items-center justify-between">

            <div>

                <p class="text-sm text-slate2-600">
                    Total Complaints
                </p>

                <h3 class="text-3xl font-bold mt-2">
                    24
                </h3>

            </div>

            <div class="w-14 h-14 rounded-2xl bg-brand-100 flex items-center justify-center">

                <x-heroicon-o-chat-bubble-left-right class="w-7 h-7 text-brand-600" />

            </div>

        </div>

    </div>

</div>

@endsection