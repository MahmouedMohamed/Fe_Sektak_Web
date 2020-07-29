<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;

use App\Request;
use App\Ride;
use Illuminate\Http\Request as WebRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use App\User;

class RequestsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function __construct()
    {
        $this->content = array();
    }
    public function index()
    {
        $user = User::findOrFail(request('user_id'));
        $this->content['requests'] =  $user->requests;
        return response()->json($this->content);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store()
    {
        $data = request()->all();
        $rules = [
            'meetPointLatitude' => ['required'],
            'meetPointLongitude' => ['required'],
            'endPointLatitude' => ['required'],
            'endPointLongitude' => ['required'],
            'numberOfNeededSeats' => ['required'],
            'time' => ['required'],
            'response' => ['boolean'],
            'userId' => ['required']
        ];
        $validator = Validator::make($data, $rules);
        if ($validator->passes()) {
            Request::create([
            'meetPointLatitude' => request('meetPointLatitude'),
            'meetPointLongitude' => request('meetPointLongitude'),
            'destinationLatitude' => request('endPointLatitude'),
            'destinationLongitude' => request('endPointLongitude'),
            'neededSeats' => request('numberOfNeededSeats'),
            'time' => request('time'),
            'user_id' => request('userId')

        ]);
            $this->content['status'] = 'done';
            return response()->json($this->content);
        } else {
            $this->content['status'] = 'undone';
            $this->content['details'] = $validator->errors()->all();
            return response()->json($this->content);
        }
    }




    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Request  $requestt
     * @return \Illuminate\Http\Response
     */
    public function update()
    {
        $data = request()->all();
        $rules = [
            'meetPointLatitude' => ['required'],
            'meetPointLongitude' => ['required'],
            'endPointLatitude' => ['required'],
            'endPointLongitude' => ['required'],
            'numberOfNeededSeats' => ['required'],
            'time' => ['required'],
            'response' => ['boolean'],
            'userId' => ['required']
        ];
        $validator = Validator::make($data, $rules);
        if ($validator->passes()) {
            $request=Request::find(request('requestId'));
            $request->update([
            'meetPointLatitude' => request('meetPointLatitude'),
            'meetPointLongitude' =>request('meetPointLatitude'),
            'destinationLatitude' =>request('endPointLatitude'),
            'destinationLongitude' => request('endPointLongitude'),
            'neededSeats' => request('numberOfNeededSeats'),
            'time' => request('time'),
            'user_id' => request('userId')
        ]);
            $this->content['status'] = 'done';
            return response()->json($this->content);
        } else {
            $this->content['status'] = 'undone';
            $this->content['details'] = $validator->errors()->all();
            return response()->json($this->content);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Request  $requestt
     * @return \Illuminate\Http\Response
     */
    public function destroy()
    {
        $requestt = Request::find(request('requestId'));
        if ($requestt!=null) {
            $requestt->delete();
            $this->content['status'] = 'done';
            return response()->json($this->content);
        } else {
            $this->content['status'] = 'already deleted';
            return response()->json($this->content);
        }
    }
    public static function x(
        $latitudeFrom,
        $longitudeFrom,
        $latitudeTo,
        $longitudeTo
    ) {
        $long1 = deg2rad($longitudeFrom);
        $long2 = deg2rad($longitudeTo);
        $lat1 = deg2rad($latitudeFrom);
        $lat2 = deg2rad($latitudeTo);

        $dlong = $long2 - $long1;
        $dlati = $lat2 - $lat1;

        $val = pow(sin($dlati/2), 2)+cos($lat1)*cos($lat2)*pow(sin($dlong/2), 2);

        $res = 2 * asin(sqrt($val));

        $radius = 3958.756;

        return ($res*$radius);
    }


    public function viewAvailableRides(Request $request)
    {
        $request = Request::findOrFail(request('id'));
        if ($request->response == false) {
            $rides = Ride::all()
            ->/*where('destination', request('destination')*/
            where('user_id', '<>', $request->user_id)
            ->where('time', '>=', $request->time)
            ->where('availableSeats', '>=', $request->neededSeats)
            ->where('available', true);

            $filtered = $rides->filter(function ($value, $key) use ($request) {
                return (self::x(
                    $request->destinationLatitude,
                    $request->destinationLongitude,
                    $value->destinationLatitude,
                    $value->destinationLongitude
                )<5);
            });

            $this->content['rides'] = $filtered;
            return response()->json($this->content);
        } else {
            $this->content['rides'] = $request->ride;
            return response()->json($this->content);
        }
    }























    public function sendRequest($request_id, $ride_id)
    {
        $requestt = Request::find($request_id);
        $requestt->ride_id = $ride_id;
        $requestt->save();
        session()->flash('flashMessage', 'Request is sent', ['timeout' => 100]);
        $requestts=Request::all()->where('id', '<>', $requestt->id);
        return view('requestts.index')->With('requestts', $requestts);
    }
    public function cancelRide($request_id, $ride_id)
    {
        $requestt = Request::find($request_id);
        $requestt->ride->availableSeats=$requestt->ride->availableSeats+ $requestt->neededSeats;
        $requestt->response=false;
        $requestt->ride_id = null;
        $requestt->save();
        session()->flash('flashMessage', 'Request to Ride is canceled ', ['timeout' => 100]);
        return redirect(route('requestts.index'));
    }
}
