<?php

// app/Http/Controllers/UserController.php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class UserController extends Controller
{
    public function index(Request $request)
    {
        // Search, sort and pagination logic
        $search = $request->input('search', '');
        $sortField = $request->input('sort_field', 'id');
        $sortDirection = $request->input('sort_direction', 'asc');
        
        $users = DB::table('users')
            ->where(function($query) use ($search) {
                $query->where('name', 'like', "%$search%")
                    ->orWhere('email', 'like', "%$search%")
                    ->orWhere('phone', 'like', "%$search%");
            })
            ->orderBy($sortField, $sortDirection)
            ->paginate(10);

        return view('users.index', compact('users', 'search', 'sortField', 'sortDirection'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'phone' => 'required|string|max:20',
            'profile_pic' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'resume' => 'nullable|file|mimes:pdf,doc,docx|max:5120',
        ]);

        $data = $request->only(['name', 'email', 'phone']);

        if ($request->hasFile('profile_pic')) {
            $data['profile_pic'] = $request->file('profile_pic')->store('profile_pics', 'public');
        }

        if ($request->hasFile('resume')) {
            $data['resume'] = $request->file('resume')->store('resumes', 'public');
        }

        DB::table('users')->insert($data);

        return response()->json(['success' => 'User created successfully.']);
    }

    public function edit($id)
{
    $user = DB::table('users')->where('id', $id)->first();
    if (!$user) {
        return response()->json(['error' => 'User not found.'], 404);
    }
    return response()->json($user);
}

    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $id,
            'phone' => 'required|string|max:20',
            'profile_pic' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'resume' => 'nullable|file|mimes:pdf,doc,docx|max:5120',
        ]);

        $user = DB::table('users')->where('id', $id)->first();
        if (!$user) {
            return response()->json(['error' => 'User not found.'], 404);
        }

        $data = $request->only(['name', 'email', 'phone']);

        if ($request->hasFile('profile_pic')) {
            // Delete old profile pic if exists
            if ($user->profile_pic) {
                Storage::disk('public')->delete($user->profile_pic);
            }
            $data['profile_pic'] = $request->file('profile_pic')->store('profile_pics', 'public');
        }

        if ($request->hasFile('resume')) {
            // Delete old resume if exists
            if ($user->resume) {
                Storage::disk('public')->delete($user->resume);
            }
            $data['resume'] = $request->file('resume')->store('resumes', 'public');
        }

        DB::table('users')->where('id', $id)->update($data);

        return response()->json(['success' => 'User updated successfully.']);
    }

    public function destroy($id)
    {
        $user = DB::table('users')->where('id', $id)->first();
        if (!$user) {
            return response()->json(['error' => 'User not found.'], 404);
        }

        // Delete files if they exist
        if ($user->profile_pic) {
            Storage::disk('public')->delete($user->profile_pic);
        }
        if ($user->resume) {
            Storage::disk('public')->delete($user->resume);
        }

        DB::table('users')->where('id', $id)->delete();

        return response()->json(['success' => 'User deleted successfully.']);
    }

    public function exportCSV()
    {
        $users = DB::table('users')->get();

        $headers = [
            "Content-type" => "text/csv",
            "Content-Disposition" => "attachment; filename=users.csv",
            "Pragma" => "no-cache",
            "Cache-Control" => "must-revalidate, post-check=0, pre-check=0",
            "Expires" => "0"
        ];

        $callback = function() use ($users) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['ID', 'Name', 'Email', 'Phone', 'Created At']);

            foreach ($users as $user) {
                fputcsv($file, [
                    $user->id,
                    $user->name,
                    $user->email,
                    $user->phone,
                    $user->created_at
                ]);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function exportPDF()
    {
        $users = DB::table('users')->get();

        $pdf = app('dompdf.wrapper');
        $pdf->loadView('users.pdf', compact('users'));
        
        return $pdf->download('users.pdf');
    }
}