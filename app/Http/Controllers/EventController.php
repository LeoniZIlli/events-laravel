<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;


use App\Models\Event;
use App\Models\User;


class EventController extends Controller
{

    public function index()
    {
        $search = request('search');
        if ($search) {
            $events = Event::where([
                ['title', 'like', '%' . $search . '%']
            ])->get();
        } else {
            $events = Event::all();
        }
        return view('welcome', ['events' => $events, 'search' => $search]);
    }


    public function create()
    {
        return view('events.create');
    }

    public function test(){
        return view ('events.create');
    }


    public function store(Request $request)
    {
        $event = new Event;
        $event->title = $request->title;
        $event->state = $request->state;
        $event->city = $request->city;
        $event->private = $request->private;
        $event->description = $request->description;
        $event->items = $request->items;
        $event->date = $request->date;
        //Upload Image
        if ($request->hasFile('image') && $request->file('image')->isValid()) {
            $requestImage = $request->image;
            $extension = $requestImage->extension();
            $imageName = md5($requestImage->getClientOriginalName() . strtotime("now")) . "." . $extension;
            $requestImage->move(public_path('img/events'), $imageName);
            $event->image = $imageName;
        }
        $user = auth()->user();
        $event->user_id = $user->id;
        $event->save();
        return redirect('/event/create')->with('msg', 'Event created successfully!');
    }


    public function show($id)
    {
        $event = Event::findOrFail($id);

        $user = auth()->user();
        $hasUserJoined = false;

        if ($user) {
            $userEvents = $user->eventsAsParticipant->toArray();
            foreach ($userEvents as $userEvent) {
                if ($userEvent['id'] == $id) {
                    $hasUserJoined = true;
                }
            }
        }

        $eventOwner = User::where('id', $event->user_id)->first()->toArray();

        return view('events.show', ['event' => $event, 'eventOwner' => $eventOwner, 'hasUserJoined' =>
            $hasUserJoined]);
    }

    public function dashboard()
    {
        $user = auth()->user();
        $events = $user->events;
        $eventsAsParticipant = $user->eventsAsParticipant;
        return view('events.dashboard', ['events' => $events, 'eventsasparticipant' => $eventsAsParticipant]);
    }

    public function destroy($id)
    {
        Event::findOrFail($id)->delete();
        return redirect('/dashboard')->with('msg', 'Event deleted successfully!');
    }

    public function edit($id)
    {
        $user = auth()->user();
        $event = Event::findOrFail($id);

        if ($user->id != $event->user_id) {
            return redirect('/dashboard');
        }

        return view('events.edit', ['event' => $event]);
    }

    public function update(Request $request)
    {
        $data = $request->all();
        $requestImage = $data['image'];

        if ($request->hasFile('image') && $request->file('image')->isValid()) {
            $requestImage = $request->image;
            $extension = $requestImage->extension();
            $imageName = md5($requestImage->getClientOriginalName() . strtotime("now")) . "." . $extension;
            $requestImage->move(public_path('img/events'), $imageName);
            $data['image'] = $imageName;
        }
        Event::findOrFail($request->id)->update($data);
        return redirect('/dashboard')->with('msg', 'Event edited successfully!');
    }

    public function joinEvent($id)
    {
        $user = auth()->user();

        $user->eventsAsParticipant()->attach($id);

        $event = Event::findOrFail($id);

        return redirect('/events/' . $id)->with('msg', 'Your presence has been confirmed at the event: ' .
            $event->title);
    }

    public function leaveEvent($id)
    {

        $user = auth()->user();

        $user->eventsAsParticipant()->detach($id);

        $event = Event::findOrFail($id);

        return redirect('/dashboard')->with('msg', 'Your presence in the ' . $event->title . ' event was successfully canceled.');
    }
}
