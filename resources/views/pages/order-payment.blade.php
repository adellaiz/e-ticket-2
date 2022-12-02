@extends('layouts.page')



@push('styles')
    <style type="text/tailwindcss">
        @layer utilities {
            body {
                background-color: #F9F9F9;
            }
        }
    </style>
@endpush

@section('content')
    <div class="container mx-auto py-12">
        <div class="flex w-full relative mb-4 gap-5 px-4 py-4 text-xl text-center text-slate-700">
            <p>1. Pesan</p>
            &gt;
            <p class="font-bold">2. Bayar</p>
            &gt;
            <p>3. Selesai</p>
        </div>
        @if ($order->status === \App\Models\Order::STATUS_UNPAID)
            <button class="rounded px-4 bg-slate-700 text-white font-semibold text-lg py-3 mx-auto " id="pay-button">Bayar Sekarang</button>
        @else
            Pembayaran berhasil
        @endif
    </div>
@endsection

@push('scripts')
    <script src="https://app.sandbox.midtrans.com/snap/snap.js" data-client-key="{{ config('midtrans.client_key') }}">
    </script>
    <script>
        const payButton = document.querySelector('#pay-button');
        payButton.addEventListener('click', function(e) {
            e.preventDefault();

            snap.pay('{{ $snapToken }}', {
                // Optional
                onSuccess: function(result) {
                    /* You may add your own js here, this is just example */
                    // document.getElementById('result-json').innerHTML += JSON.stringify(result, null, 2);
                    console.log(result)
                },
                // Optional
                onPending: function(result) {
                    /* You may add your own js here, this is just example */
                    // document.getElementById('result-json').innerHTML += JSON.stringify(result, null, 2);
                    console.log(result)
                },
                // Optional
                onError: function(result) {
                    /* You may add your own js here, this is just example */
                    // document.getElementById('result-json').innerHTML += JSON.stringify(result, null, 2);
                    console.log(result)
                }
            });
        });
    </script>
@endpush

