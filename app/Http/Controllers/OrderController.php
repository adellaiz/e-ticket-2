<?php

namespace App\Http\Controllers;

use App\Mail\BookingMail;
use App\Mail\OrderTicketMail;
use App\Models\CustomerOrder;
use App\Models\Event;
use App\Models\Order;
use App\Models\Ticket;
use App\Services\Midtrans\CreateSnapTokenService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;

class OrderController extends Controller
{
    //
    public function store(Request $request){

        $validator = Validator::make($request->all(), [
            'ticket_id' => 'required|exists:tickets,id',
            'customer_name' => 'required|string|max:255',
            'customer_email' => 'required|email',
            'customer_phone_number' => 'required|string',
            'customer_gender' => 'nullable|in:M,F',
            'visitors' => 'required|array',
        ]);

        if($validator->fails()){
            return redirect()->back();
        }

        $ticket = Ticket::find($request->post('ticket_id'));
        $maxOrders = $ticket->max_orders;
        $totalPerson = $ticket->total_person;

        if($maxOrders < $ticket->orders()->count() + 1){
            return redirect()->to('/');
        }

        $visitors = $request->post('visitors');
        if(count($visitors) > $totalPerson){
            return redirect()->back();
        }

        $order = new Order([
            'ticket_id' => $ticket->id
        ]);
        $order->save();

        $mails = collect();
        $customer = new CustomerOrder([
            'order_id' => $order->id,
            'name' => $request->post('customer_name'),
            'gender' => $request->post('customer_gender'),
            'email' => $request->post('customer_email'),
            'phone_number' => $request->post('customer_phone_number'),
            'type' => Order::TYPE_CUSTOMER
        ]);
        $mails->add($request->post('customer_email'));
        $customer->save();

        foreach ($visitors as $visitor){
            $visitor = new CustomerOrder([
                'order_id' => $order->id,
                'name' => $visitor['name'],
                'gender' => $visitor['gender'],
                'email' => $visitor['email'],
                'phone_number' => $visitor['phone_number'],
                'ktp_number' => $visitor['ktp_number'],
                'type' => Order::TYPE_VISITOR
            ]);
            $mails->add($visitor->email);
            $visitor->save();
        }

        Mail::bcc($mails->toArray())->send(new BookingMail([
            'order' => $order,
        ]));

        return redirect(url('/orders/' . $order->booking_code));
    }

    public function show($bookingCode){

        $order = Order::where('booking_code', $bookingCode)->firstOrFail();

        $snapToken = $order->snap_token;
        if (is_null($snapToken)) {
            // If snap token is still NULL, generate snap token and save it to database
            $midtrans = new CreateSnapTokenService($order);
            $snapToken = $midtrans->getSnapToken();

            $order->snap_token = $snapToken;
            $order->save();
        }

        if($order->status === Order::STATUS_PAID){
            return view('pages.order-success', [
                'order' => $order,
                'ticket' => $order->ticket,
            ]);
        }

        return view('pages.order-payment', compact('order', 'snapToken'));
    }

    public function pdf($bookingCode){
        $order = Order::where('booking_code', $bookingCode)->firstOrFail();

        $pdf = PDF::loadView('attachments.order-pdf', compact('order'));
        return $pdf->stream('order_'.$order->booking_code.'.pdf');
    }
}
