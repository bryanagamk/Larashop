<?php

namespace App\Http\Controllers;

use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            if (Gate::allows('manage-users')) return $next($request);
            abort(403, 'Anda tidak memiliki cukup hak akses');
        });
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $users = User::paginate(10);
        $status = $request->status;
        if ($status) {
            $users = User::where('status', $status)->paginate(10);
        } else {
            $users = User::paginate(10);
        }

        $filterKeyword = $request->get('keyword');
        if ($filterKeyword) {
            if ($status) {
                $users = User::where('email', 'LIKE', "%$filterKeyword%")
                    ->where('status', $status)
                    ->paginate(10);
            } else {
                $users = User::where('email', 'LIKE', "%$filterKeyword%")
                    ->paginate(10);
            }
        }
        return view('users.index', ['users' => $users]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view("users.create");
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        Validator::make($request->all(), [
            "name" => "required|min:5|max:100",
            "username" => "required|min:5|max:20",
            "roles" => "required",
            "phone" => "required|digits_between:10,12",
            "address" => "required|min:20|max:200",
            "avatar" => "required",
            "email" => "required|email",
            "password" => "required",
            "password_confirmation" => "required|same:password"
        ])->validate();

        $user = new User();
        $user->name = $request->name;
        $user->username = $request->username;
        $user->roles = json_encode($request->roles);
        $user->address = $request->address;
        $user->phone = $request->phone;
        $user->email = $request->email;
        $user->password = Hash::make($request->password);

        if ($request->avatar) {
            $file = $request->avatar->store('avatar', 'public');

            $user->avatar = $file;
        }

        $user->save();

        return redirect('/users/create')->with('status', 'User successfully created.');
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $user = User::findOrFail($id);
        return view('users.show', ['user' => $user]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $user = User::findOrFail($id);

        return view('users.edit', ['user' => $user]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        Validator::make($request->all(), [
            "name" => "required|min:5|max:100",
            "roles" => "required",
            "phone" => "required|digits_between:10,12",
            "address" => "required|min:20|max:200",
        ])->validate();

        $user = User::findOrFail($id);
        $user->name = $request->name;
        $user->roles = json_encode($request->roles);
        $user->address = $request->address;
        $user->phone = $request->phone;

        if ($request->avatar && file_exists(storage_path('app/public/' . $user->avatar))) {
            Storage::delete('public/' . $user->avatar);
            $file = $request->avatar->store('avatar', 'public');

            $user->avatar = $file;
        }

        $user->save();

        return redirect('users/edit', [$id])->with('status', 'User successfully updated');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $user = User::findOrFail($id);
        $user->delete();

        return redirect('users/index')->with('status', 'User successfully delete');
    }
}
