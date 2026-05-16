<?php

namespace App\Http\Controllers;

use App\Label;
use App\Note;
use App\NoteAttachment;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class ApiController extends Controller
{
    private $defaultPreferences = [
        'darkMode' => false,
        'noteFontSize' => 14,
        'pageBackground' => '#f7f3ff',
        'view' => 'grid',
        'sort' => 'modified',
        'autoSaveEnabled' => true,
        'confirmDelete' => true,
    ];

    public function health()
    {
        return response()->json(['success' => true, 'app' => 'NoteNoty']);
    }

    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email',
            'password' => 'required|string|min:6|confirmed',
            'activation_home_url' => 'nullable|string|max:2048',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator);
        }

        $autoActivate = filter_var(env('NOTE_NOTY_AUTO_ACTIVATE', false), FILTER_VALIDATE_BOOLEAN);
        $token = Str::random(48);

        $user = User::create([
            'name' => $request->name,
            'email' => strtolower($request->email),
            'password' => Hash::make($request->password),
            'role' => $request->input('role', 'Người dùng NoteNoty'),
            'preferences' => json_encode($this->defaultPreferences),
            'is_active' => $autoActivate ? 1 : 0,
            'activation_token' => $autoActivate ? null : $token,
        ]);

        $user->api_token = Str::random(60);
        $user->save();

        $activationHomeUrl = $this->validatedAppUrl($request->input('activation_home_url'));

        if (!$autoActivate) {
            $this->sendActivationMail($user, $activationHomeUrl);
        }

        return response()->json([
            'success' => true,
            'token' => $user->api_token,
            'user' => $this->serializeUser($user),
            'activation_url' => $autoActivate ? null : $this->activationUrl($token, $activationHomeUrl),
        ], 201);
    }

    public function activate(Request $request, $token)
    {
        $user = User::where('activation_token', $token)->first();

        if (!$user) {
            return response()->view('emails.activation_result', [
                'success' => false,
                'title' => 'Liên kết không hợp lệ',
                'message' => 'Liên kết kích hoạt không hợp lệ hoặc đã được sử dụng.',
            ], 404);
        }

        if ($user->is_active) {
            return response()->view('emails.activation_result', [
                'success' => true,
                'title' => 'Tài khoản đã được kích hoạt',
                'message' => 'Bạn đã kích hoạt tài khoản rồi. Hãy quay lại NoteNoty để tiếp tục sử dụng.',
            ]);
        }

        $user->is_active = 1;
        if (!$user->api_token) {
            $user->api_token = Str::random(60);
        }
        $user->save();

        $homeUrl = $this->activationHomeUrl($request);
        if ($homeUrl) {
            return redirect()->away($this->appendQuery($homeUrl, [
                'activated' => 1,
                'api_token' => $user->api_token,
                'email' => $user->email,
            ]));
        }

        return response()->view('emails.activation_result', [
            'success' => true,
            'title' => 'Kích hoạt thành công',
            'message' => 'Tài khoản NoteNoty của bạn đã được kích hoạt. Bạn có thể quay lại ứng dụng để dùng đầy đủ chức năng.',
        ]);
    }

    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator);
        }

        $user = User::where('email', strtolower($request->email))->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json(['success' => false, 'message' => 'Email hoặc mật khẩu chưa đúng.'], 401);
        }

        if (!$user->is_active && !$user->activation_token) {
            $user->activation_token = Str::random(48);
        }

        $user->api_token = Str::random(60);
        $user->save();

        return response()->json([
            'success' => true,
            'token' => $user->api_token,
            'user' => $this->serializeUser($user),
        ]);
    }

    public function logout(Request $request)
    {
        $user = $this->currentUser($request);
        if ($user) {
            $user->api_token = null;
            $user->save();
        }

        return response()->json(['success' => true]);
    }

    public function forgotPassword(Request $request)
    {
        $validator = Validator::make($request->all(), ['email' => 'required|email']);

        if ($validator->fails()) {
            return $this->validationError($validator);
        }

        $user = User::where('email', strtolower($request->email))->first();

        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Không tìm thấy tài khoản với email này.'], 404);
        }

        $token = Str::random(64);
        DB::table('password_resets')->updateOrInsert(
            ['email' => $user->email],
            [
                'token' => Hash::make($token),
                'created_at' => now(),
            ]
        );

        $this->sendPasswordResetMail($user, $token, $request);

        return response()->json([
            'success' => true,
            'message' => 'Liên kết khôi phục mật khẩu đã được gửi đến email của bạn.',
        ]);
    }

    public function resetPasswordPage(Request $request, $token)
    {
        return response()->view('emails.password_reset_page', [
            'token' => $token,
            'email' => $request->query('email', ''),
            'apiBase' => url('/api'),
            'mode' => 'reset',
            'title' => 'Đặt lại mật khẩu',
            'subtitle' => 'Nhập mật khẩu mới cho tài khoản NoteNoty của bạn.',
            'buttonText' => 'Cập nhật mật khẩu',
            'loginUrl' => $this->loginUrl($request),
        ]);
    }

    public function resetPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'token' => 'required|string',
            'password' => 'required|string|min:6|confirmed',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator);
        }

        $row = DB::table('password_resets')->where('email', strtolower($request->email))->first();

        if (!$row || !Hash::check($request->token, $row->token)) {
            return response()->json(['success' => false, 'message' => 'Liên kết khôi phục không hợp lệ.'], 422);
        }

        if ($row->created_at && strtotime($row->created_at) < now()->subHours(2)->timestamp) {
            return response()->json(['success' => false, 'message' => 'Liên kết khôi phục đã hết hạn.'], 422);
        }

        $user = User::where('email', strtolower($request->email))->first();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Không tìm thấy tài khoản.'], 404);
        }

        $user->password = Hash::make($request->password);
        $user->api_token = null;
        $user->save();

        DB::table('password_resets')->where('email', $user->email)->delete();

        return response()->json(['success' => true, 'message' => 'Mật khẩu đã được cập nhật.']);
    }

    public function me(Request $request)
    {
        $user = $this->requireUser($request);
        if (!$user) {
            return $this->unauthorized();
        }

        return response()->json([
            'success' => true,
            'user' => $this->serializeUser($user),
            'preferences' => $this->userPreferences($user),
        ]);
    }

    public function updateProfile(Request $request)
    {
        $user = $this->requireUser($request);
        if (!$user) {
            return $this->unauthorized();
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'role' => 'nullable|string|max:255',
            'avatar' => 'nullable|string|max:3000000',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator);
        }

        $user->name = $request->name;
        $user->role = $request->input('role', $user->role);

        if ($request->boolean('remove_avatar')) {
            $user->avatar = null;
        } elseif ($request->filled('avatar') && Str::startsWith($request->avatar, 'data:image/')) {
            $user->avatar = $this->storeDataImage($request->avatar, 'uploads/avatars');
        }

        $user->save();

        return response()->json(['success' => true, 'user' => $this->serializeUser($user)]);
    }

    public function changePassword(Request $request)
    {
        $user = $this->requireUser($request);
        if (!$user) {
            return $this->unauthorized();
        }

        if (!$user->is_active) {
            return response()->json(['success' => false, 'message' => 'Hãy kích hoạt tài khoản trước khi đổi mật khẩu.'], 403);
        }

        $validator = Validator::make($request->all(), [
            'current_password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator);
        }

        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json(['success' => false, 'message' => 'Mật khẩu hiện tại chưa đúng.'], 422);
        }

        $token = Str::random(64);
        DB::table('password_change_requests')->where('user_id', $user->id)->delete();
        DB::table('password_change_requests')->insert([
            'user_id' => $user->id,
            'token' => $token,
            'new_password' => null,
            'created_at' => now(),
        ]);

        $this->sendPasswordChangeMail($user, $token);

        return response()->json([
            'success' => true,
            'message' => 'Email xác nhận đổi mật khẩu đã được gửi. Vui lòng mở email để hoàn tất.',
        ]);
    }

    public function confirmPasswordChange(Request $request, $token)
    {
        $row = DB::table('password_change_requests')->where('token', $token)->first();

        if (!$row) {
            return response()->view('emails.activation_result', [
                'success' => false,
                'title' => 'Liên kết không hợp lệ',
                'message' => 'Liên kết xác nhận đổi mật khẩu không hợp lệ hoặc đã được sử dụng.',
            ], 404);
        }

        if ($row->created_at && strtotime($row->created_at) < now()->subHours(2)->timestamp) {
            DB::table('password_change_requests')->where('id', $row->id)->delete();

            return response()->view('emails.activation_result', [
                'success' => false,
                'title' => 'Liên kết đã hết hạn',
                'message' => 'Yêu cầu đổi mật khẩu đã quá hạn. Vui lòng thực hiện lại trong dashboard.',
            ], 422);
        }

        $user = User::find($row->user_id);
        if (!$user) {
            return response()->view('emails.activation_result', [
                'success' => false,
                'title' => 'Không tìm thấy tài khoản',
                'message' => 'Tài khoản yêu cầu đổi mật khẩu không còn tồn tại.',
            ], 404);
        }

        return response()->view('emails.password_reset_page', [
            'token' => $token,
            'email' => $user->email,
            'apiBase' => url('/api'),
            'mode' => 'change',
            'title' => 'Tạo mật khẩu mới',
            'subtitle' => 'Nhập mật khẩu mới cho tài khoản NoteNoty của bạn.',
            'buttonText' => 'Cập nhật mật khẩu',
            'loginUrl' => $this->loginUrl($request),
        ]);
    }

    public function completePasswordChange(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'token' => 'required|string',
            'password' => 'required|string|min:6|confirmed',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator);
        }

        $row = DB::table('password_change_requests')->where('token', $request->token)->first();

        if (!$row) {
            return response()->json(['success' => false, 'message' => 'Liên kết đổi mật khẩu không hợp lệ hoặc đã được sử dụng.'], 422);
        }

        if ($row->created_at && strtotime($row->created_at) < now()->subHours(2)->timestamp) {
            DB::table('password_change_requests')->where('id', $row->id)->delete();
            return response()->json(['success' => false, 'message' => 'Liên kết đổi mật khẩu đã hết hạn. Vui lòng thực hiện lại trong dashboard.'], 422);
        }

        $user = User::find($row->user_id);
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Không tìm thấy tài khoản cần đổi mật khẩu.'], 404);
        }

        if (Hash::check($request->password, $user->password)) {
            return response()->json(['success' => false, 'message' => 'Mật khẩu mới không được trùng mật khẩu hiện tại.'], 422);
        }

        $user->password = Hash::make($request->password);
        $user->api_token = null;
        $user->save();

        DB::table('password_change_requests')->where('id', $row->id)->delete();

        return response()->json([
            'success' => true,
            'message' => 'Mật khẩu đã được cập nhật. Vui lòng đăng nhập lại bằng mật khẩu mới.',
        ]);
    }

    public function updatePreferences(Request $request)
    {
        $user = $this->requireUser($request);
        if (!$user) {
            return $this->unauthorized();
        }

        $prefs = array_merge($this->defaultPreferences, $request->input('preferences', $request->all()));
        $user->preferences = json_encode($prefs);
        $user->dark_mode = !empty($prefs['darkMode']) ? 1 : 0;
        $user->view_preference = ($prefs['view'] ?? 'grid') === 'list' ? 'list' : 'grid';
        $user->save();

        return response()->json(['success' => true, 'preferences' => $this->userPreferences($user)]);
    }

    public function bootstrap(Request $request)
    {
        $user = $this->requireUser($request);
        if (!$user) {
            return $this->unauthorized();
        }

        $labels = Label::where('user_id', $user->id)->orderBy('name')->get()->map(function ($label) {
            return $this->serializeLabel($label);
        })->values();

        $notes = Note::with(['labels', 'attachments', 'sharedUsers', 'user'])
            ->where('user_id', $user->id)
            ->orderBy('is_pinned', 'desc')
            ->orderBy('pinned_at', 'desc')
            ->orderBy('updated_at', 'desc')
            ->get()
            ->map(function ($note) use ($user) {
                return $this->serializeNote($note, $user);
            })->values();

        $received = Note::with(['labels', 'attachments', 'sharedUsers', 'user'])
            ->whereHas('sharedUsers', function ($query) use ($user) {
                $query->where('users.id', $user->id);
            })
            ->orderBy('updated_at', 'desc')
            ->get()
            ->map(function ($note) use ($user) {
                return $this->serializeNote($note, $user);
            })->values();

        return response()->json([
            'success' => true,
            'user' => $this->serializeUser($user),
            'preferences' => $this->userPreferences($user),
            'labels' => $labels,
            'notes' => $notes,
            'receivedNotes' => $received,
        ]);
    }

    public function authorizeRealtime(Request $request)
    {
        $user = $this->requireUser($request);
        if (!$user) {
            return $this->unauthorized();
        }

        $ids = collect($request->input('note_ids', []))
            ->map(function ($id) {
                return (int) $id;
            })
            ->filter()
            ->unique()
            ->values()
            ->all();

        if (empty($ids)) {
            return response()->json([
                'success' => true,
                'user_id' => $user->id,
                'note_ids' => [],
            ]);
        }

        $ownedIds = Note::where('user_id', $user->id)
            ->whereIn('id', $ids)
            ->pluck('id')
            ->all();

        $sharedIds = DB::table('shared_notes')
            ->where('shared_with_user_id', $user->id)
            ->whereIn('note_id', $ids)
            ->pluck('note_id')
            ->all();

        return response()->json([
            'success' => true,
            'user_id' => $user->id,
            'note_ids' => array_values(array_unique(array_merge($ownedIds, $sharedIds))),
        ]);
    }

    public function showNote(Request $request, $id)
    {
        $user = $this->requireUser($request);
        if (!$user) {
            return $this->unauthorized();
        }

        $note = Note::with(['labels', 'attachments', 'sharedUsers', 'user'])->findOrFail($id);
        if (!$this->canView($user, $note)) {
            return $this->forbidden();
        }

        return response()->json([
            'success' => true,
            'note' => $this->serializeNote($note, $user),
        ]);
    }

    public function storeNote(Request $request)
    {
        $user = $this->requireUser($request);
        if (!$user) {
            return $this->unauthorized();
        }

        $validator = Validator::make($request->all(), [
            'title' => 'nullable|string|max:200',
            'content' => 'nullable|string',
            'color' => 'nullable|string|max:30',
            'labels' => 'nullable|array',
            'images' => 'nullable|array|max:6',
            'images.*' => 'nullable|string|max:3000000',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator);
        }

        if (!$request->filled('title') && !$request->filled('content') && empty($request->input('images', []))) {
            return response()->json(['success' => false, 'message' => 'Ghi chú cần có tiêu đề, nội dung hoặc ảnh.'], 422);
        }

        $note = Note::create([
            'user_id' => $user->id,
            'title' => $request->input('title', 'Không có tiêu đề'),
            'content' => $request->input('content', ''),
            'color' => $request->input('color', 'nc-lav'),
        ]);

        $this->syncLabels($note, $user, $request->input('labels', []));
        $this->syncImages($note, $request->input('images', []));

        $fresh = $note->fresh(['labels', 'attachments', 'sharedUsers', 'user']);
        $this->broadcastNoteEvent($request, 'note.created', $fresh);

        return response()->json(['success' => true, 'note' => $this->serializeNote($fresh, $user)], 201);
    }

    public function updateNote(Request $request, $id)
    {
        $user = $this->requireUser($request);
        if (!$user) {
            return $this->unauthorized();
        }

        $note = Note::with(['labels', 'attachments', 'sharedUsers', 'user'])->findOrFail($id);

        if (!$this->canEdit($user, $note)) {
            return $this->forbidden();
        }

        $validator = Validator::make($request->all(), [
            'title' => 'nullable|string|max:200',
            'content' => 'nullable|string',
            'color' => 'nullable|string|max:30',
            'labels' => 'nullable|array',
            'images' => 'nullable|array|max:6',
            'images.*' => 'nullable|string|max:3000000',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator);
        }

        $note->title = $request->input('title', $note->title ?: 'Không có tiêu đề');
        $note->content = $request->input('content', $note->content);
        $note->color = $request->input('color', $note->color ?: 'nc-lav');
        $note->save();

        if ($this->isOwner($user, $note)) {
            $this->syncLabels($note, $user, $request->input('labels', []));
        }

        $this->syncImages($note, $request->input('images', []));

        $fresh = $note->fresh(['labels', 'attachments', 'sharedUsers', 'user']);
        $this->broadcastNoteEvent($request, 'note.updated', $fresh);

        return response()->json(['success' => true, 'note' => $this->serializeNote($fresh, $user)]);
    }

    public function destroyNote(Request $request, $id)
    {
        $user = $this->requireUser($request);
        if (!$user) {
            return $this->unauthorized();
        }

        $note = Note::where('user_id', $user->id)->findOrFail($id);
        $this->broadcastNoteEvent($request, 'note.deleted', $note, ['noteId' => $note->id]);
        $note->labels()->detach();
        DB::table('shared_notes')->where('note_id', $note->id)->delete();
        $note->attachments()->delete();
        $note->delete();

        return response()->json(['success' => true]);
    }

    public function togglePin(Request $request, $id)
    {
        $user = $this->requireUser($request);
        if (!$user) {
            return $this->unauthorized();
        }

        $note = Note::where('user_id', $user->id)->findOrFail($id);
        $note->is_pinned = !$note->is_pinned;
        $note->pinned_at = $note->is_pinned ? now() : null;
        $note->save();

        $fresh = $note->fresh(['labels', 'attachments', 'sharedUsers', 'user']);
        $this->broadcastNoteEvent($request, 'note.pinned', $fresh);

        return response()->json(['success' => true, 'note' => $this->serializeNote($fresh, $user)]);
    }

    public function unlockNote(Request $request, $id)
    {
        $user = $this->requireUser($request);
        if (!$user) {
            return $this->unauthorized();
        }

        $note = Note::findOrFail($id);
        if (!$this->canView($user, $note)) {
            return $this->forbidden();
        }

        if (!$note->password || Hash::check($request->input('password', ''), $note->password)) {
            return response()->json([
                'success' => true,
                'note' => $this->serializeNote($note->fresh(['labels', 'attachments', 'sharedUsers', 'user']), $user, true),
            ]);
        }

        return response()->json(['success' => false, 'message' => 'Sai mật khẩu ghi chú.'], 422);
    }

    public function setNotePassword(Request $request, $id)
    {
        $user = $this->requireUser($request);
        if (!$user) {
            return $this->unauthorized();
        }

        $note = Note::where('user_id', $user->id)->findOrFail($id);

        if ($note->password && !Hash::check($request->input('current_password', ''), $note->password)) {
            return response()->json(['success' => false, 'message' => 'Mật khẩu hiện tại chưa đúng.'], 422);
        }

        $validator = Validator::make($request->all(), [
            'new_password' => 'required|string|min:4|confirmed',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator);
        }

        $note->password = Hash::make($request->new_password);
        $note->save();

        $fresh = $note->fresh(['labels', 'attachments', 'sharedUsers', 'user']);
        $this->broadcastNoteEvent($request, 'note.password.updated', $fresh);

        return response()->json(['success' => true, 'note' => $this->serializeNote($fresh, $user)]);
    }

    public function disableNotePassword(Request $request, $id)
    {
        $user = $this->requireUser($request);
        if (!$user) {
            return $this->unauthorized();
        }

        $note = Note::where('user_id', $user->id)->findOrFail($id);

        if ($note->password && !Hash::check($request->input('current_password', ''), $note->password)) {
            return response()->json(['success' => false, 'message' => 'Nhập đúng mật khẩu hiện tại để tắt khóa.'], 422);
        }

        $note->password = null;
        $note->save();

        $fresh = $note->fresh(['labels', 'attachments', 'sharedUsers', 'user']);
        $this->broadcastNoteEvent($request, 'note.password.disabled', $fresh);

        return response()->json(['success' => true, 'note' => $this->serializeNote($fresh, $user)]);
    }

    public function storeLabel(Request $request)
    {
        $user = $this->requireUser($request);
        if (!$user) {
            return $this->unauthorized();
        }

        $validator = Validator::make($request->all(), ['name' => 'required|string|max:50']);
        if ($validator->fails()) {
            return $this->validationError($validator);
        }

        $exists = Label::where('user_id', $user->id)
            ->whereRaw('LOWER(name) = ?', [strtolower($request->name)])
            ->exists();

        if ($exists) {
            return response()->json(['success' => false, 'message' => 'Nhãn này đã tồn tại.'], 422);
        }

        $label = Label::create(['user_id' => $user->id, 'name' => $request->name]);

        return response()->json(['success' => true, 'label' => $this->serializeLabel($label)], 201);
    }

    public function updateLabel(Request $request, $id)
    {
        $user = $this->requireUser($request);
        if (!$user) {
            return $this->unauthorized();
        }

        $validator = Validator::make($request->all(), ['name' => 'required|string|max:50']);
        if ($validator->fails()) {
            return $this->validationError($validator);
        }

        $label = Label::where('user_id', $user->id)->findOrFail($id);
        $label->name = $request->name;
        $label->save();

        return response()->json(['success' => true, 'label' => $this->serializeLabel($label)]);
    }

    public function destroyLabel(Request $request, $id)
    {
        $user = $this->requireUser($request);
        if (!$user) {
            return $this->unauthorized();
        }

        $label = Label::where('user_id', $user->id)->findOrFail($id);
        $label->notes()->detach();
        $label->delete();

        return response()->json(['success' => true]);
    }

    public function shareNote(Request $request, $id)
    {
        $user = $this->requireUser($request);
        if (!$user) {
            return $this->unauthorized();
        }

        if (!$user->is_active) {
            return response()->json(['success' => false, 'message' => 'Hãy kích hoạt tài khoản trước khi chia sẻ ghi chú.'], 403);
        }

        $note = Note::where('user_id', $user->id)->findOrFail($id);
        $receiver = User::where('email', strtolower($request->input('email')))->first();

        if (!$receiver) {
            return response()->json(['success' => false, 'message' => 'Không tìm thấy người dùng với email này.'], 404);
        }

        if ($receiver->id === $user->id) {
            return response()->json(['success' => false, 'message' => 'Không thể tự chia sẻ cho chính mình.'], 422);
        }

        $permission = $request->input('permission') === 'editable' ? 'edit' : 'view';

        DB::table('shared_notes')->updateOrInsert(
            ['note_id' => $note->id, 'shared_with_user_id' => $receiver->id],
            [
                'shared_by_user_id' => $user->id,
                'permission' => $permission,
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );

        $fresh = $note->fresh(['labels', 'attachments', 'sharedUsers', 'user']);
        $this->broadcastNoteEvent($request, 'note.share.updated', $fresh);

        return response()->json(['success' => true, 'note' => $this->serializeNote($fresh, $user)]);
    }

    public function updateShare(Request $request, $id, $shareId)
    {
        $user = $this->requireUser($request);
        if (!$user) {
            return $this->unauthorized();
        }

        if (!$user->is_active) {
            return response()->json(['success' => false, 'message' => 'Hãy kích hoạt tài khoản trước khi chỉnh quyền chia sẻ.'], 403);
        }

        $note = Note::where('user_id', $user->id)->findOrFail($id);
        $permission = $request->input('permission') === 'editable' ? 'edit' : 'view';

        DB::table('shared_notes')
            ->where('id', $shareId)
            ->where('note_id', $note->id)
            ->update(['permission' => $permission, 'updated_at' => now()]);

        $fresh = $note->fresh(['labels', 'attachments', 'sharedUsers', 'user']);
        $this->broadcastNoteEvent($request, 'note.share.updated', $fresh);

        return response()->json(['success' => true, 'note' => $this->serializeNote($fresh, $user)]);
    }

    public function revokeShare(Request $request, $id, $shareId)
    {
        $user = $this->requireUser($request);
        if (!$user) {
            return $this->unauthorized();
        }

        if (!$user->is_active) {
            return response()->json(['success' => false, 'message' => 'Hãy kích hoạt tài khoản trước khi thu hồi chia sẻ.'], 403);
        }

        $note = Note::where('user_id', $user->id)->findOrFail($id);

        DB::table('shared_notes')
            ->where('id', $shareId)
            ->where('note_id', $note->id)
            ->delete();

        $fresh = $note->fresh(['labels', 'attachments', 'sharedUsers', 'user']);
        $this->broadcastNoteEvent($request, 'note.share.updated', $fresh);

        return response()->json(['success' => true, 'note' => $this->serializeNote($fresh, $user)]);
    }

    private function currentUser(Request $request)
    {
        $header = $request->headers->get('Authorization', '');
        $token = Str::startsWith($header, 'Bearer ') ? substr($header, 7) : $request->input('api_token');

        if (!$token) {
            return null;
        }

        return User::where('api_token', $token)->first();
    }

    private function requireUser(Request $request)
    {
        return $this->currentUser($request);
    }

    private function serializeUser(User $user)
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role ?: 'Người dùng NoteNoty',
            'avatar' => $this->assetUrl($user->avatar),
            'joinedDate' => optional($user->created_at)->format('d/m/Y'),
            'emailVerified' => (bool) $user->is_active,
            'initials' => $this->initials($user->name),
        ];
    }

    private function serializeLabel(Label $label)
    {
        return [
            'id' => $label->id,
            'name' => $label->name,
            'color' => $this->labelColor($label->id),
        ];
    }

    private function serializeNote(Note $note, User $viewer = null, $includeProtected = false)
    {
        $note->load(['labels', 'attachments', 'sharedUsers', 'user']);

        $shares = $note->sharedUsers->map(function ($sharedUser) {
            return [
                'id' => $sharedUser->pivot->id,
                'user_id' => $sharedUser->id,
                'email' => $sharedUser->email,
                'name' => $sharedUser->name,
                'perm' => $sharedUser->pivot->permission === 'edit' ? 'editable' : 'readonly',
                'sharedAt' => $this->toMilliseconds($sharedUser->pivot->created_at),
            ];
        })->values();

        $viewerShare = null;
        if ($viewer && $viewer->id !== $note->user_id) {
            $viewerShare = $shares->firstWhere('user_id', $viewer->id);
        }

        return [
            'id' => $note->id,
            'title' => $note->title ?: 'Không có tiêu đề',
            'content' => ($note->password && !$includeProtected) ? '' : ($note->content ?: ''),
            'color' => $note->color ?: 'nc-lav',
            'labels' => $note->labels->pluck('id')->values(),
            'pinned' => (bool) $note->is_pinned,
            'pinnedAt' => $this->toMilliseconds($note->pinned_at),
            'locked' => !empty($note->password),
            'password' => '',
            'shared' => $shares->count() > 0,
            'shares' => $shares,
            'images' => ($note->password && !$includeProtected) ? [] : $note->attachments->map(function ($attachment) {
                return $this->assetUrl($attachment->file_path);
            })->filter()->values(),
            'createdAt' => $this->toMilliseconds($note->created_at),
            'updatedAt' => $this->toMilliseconds($note->updated_at),
            'ownerId' => $note->user_id,
            'ownerName' => optional($note->user)->name,
            'ownerEmail' => optional($note->user)->email,
            'canEdit' => !$viewer || $viewer->id === $note->user_id || ($viewerShare && $viewerShare['perm'] === 'editable'),
            'received' => $viewer ? $viewer->id !== $note->user_id : false,
        ];
    }

    private function userPreferences(User $user)
    {
        $prefs = json_decode($user->preferences ?: '[]', true);
        if (!is_array($prefs)) {
            $prefs = [];
        }

        $merged = array_merge($this->defaultPreferences, $prefs);
        $merged['darkMode'] = array_key_exists('darkMode', $prefs) ? (bool) $prefs['darkMode'] : (bool) $user->dark_mode;
        $merged['view'] = $prefs['view'] ?? ($user->view_preference ?: 'grid');

        return $merged;
    }

    private function syncLabels(Note $note, User $user, array $labelIds)
    {
        $validIds = Label::where('user_id', $user->id)
            ->whereIn('id', $labelIds)
            ->pluck('id')
            ->all();

        $note->labels()->sync($validIds);
    }

    private function syncImages(Note $note, array $images)
    {
        $paths = [];

        foreach ($images as $image) {
            if (!is_string($image) || $image === '') {
                continue;
            }

            if (Str::startsWith($image, 'data:image/')) {
                $storedPath = $this->storeDataImage($image, 'uploads/notes');
                if ($storedPath) {
                    $paths[] = $storedPath;
                }
                continue;
            }

            $marker = '/uploads/notes/';
            $pos = strpos($image, $marker);
            if ($pos !== false) {
                $paths[] = ltrim(substr($image, $pos + 1), '/');
            }
        }

        $note->attachments()->delete();

        foreach (array_unique($paths) as $path) {
            NoteAttachment::create(['note_id' => $note->id, 'file_path' => $path]);
        }
    }

    private function storeDataImage($dataUrl, $folder)
    {
        if (!preg_match('/^data:image\/(png|jpe?g|gif|webp);base64,/', $dataUrl, $matches)) {
            return null;
        }

        $extension = $matches[1] === 'jpeg' ? 'jpg' : $matches[1];
        $data = substr($dataUrl, strpos($dataUrl, ',') + 1);
        $binary = base64_decode($data);

        if ($binary === false) {
            return null;
        }

        $targetDir = public_path($folder);
        if (!is_dir($targetDir) && !@mkdir($targetDir, 0755, true)) {
            return null;
        }

        $filename = Str::random(32) . '.' . $extension;
        $stored = @file_put_contents($targetDir . DIRECTORY_SEPARATOR . $filename, $binary);
        if ($stored === false) {
            return null;
        }

        return trim($folder, '/') . '/' . $filename;
    }

    private function canView(User $user, Note $note)
    {
        return $this->isOwner($user, $note)
            || DB::table('shared_notes')->where('note_id', $note->id)->where('shared_with_user_id', $user->id)->exists();
    }

    private function canEdit(User $user, Note $note)
    {
        return $this->isOwner($user, $note)
            || DB::table('shared_notes')
                ->where('note_id', $note->id)
                ->where('shared_with_user_id', $user->id)
                ->where('permission', 'edit')
                ->exists();
    }

    private function isOwner(User $user, Note $note)
    {
        return (int) $note->user_id === (int) $user->id;
    }

    private function requestClientId(Request $request)
    {
        return $request->headers->get('X-NoteNoty-Client-Id', '');
    }

    private function broadcastNoteEvent(Request $request, $event, Note $note, array $extra = [])
    {
        $note->load('sharedUsers');
        $userIds = array_values(array_unique(array_merge(
            [$note->user_id],
            $note->sharedUsers->pluck('id')->all()
        )));

        $this->broadcastRealtime(array_merge([
            'event' => $event,
            'noteId' => $note->id,
            'userIds' => $userIds,
            'actorClientId' => $this->requestClientId($request),
        ], $extra));
    }

    private function broadcastRealtime(array $payload)
    {
        $baseUrl = rtrim(env('NOTE_NOTY_REALTIME_HTTP_URL', 'http://127.0.0.1:8011'), '/');
        $secret = env('NOTE_NOTY_REALTIME_SECRET', 'notenoty-local-realtime-secret');

        if (!$baseUrl || !$secret) {
            return;
        }

        $body = json_encode($payload);
        if ($body === false) {
            return;
        }

        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => implode("\r\n", [
                    'Content-Type: application/json',
                    'Accept: application/json',
                    'X-NoteNoty-Realtime-Secret: ' . $secret,
                ]),
                'content' => $body,
                'timeout' => 0.6,
                'ignore_errors' => true,
            ],
        ]);

        @file_get_contents($baseUrl . '/broadcast', false, $context);
    }

    private function assetUrl($path)
    {
        if (!$path) {
            return '';
        }

        if (Str::startsWith($path, ['http://', 'https://', 'data:'])) {
            return $path;
        }

        if (Str::startsWith($path, 'public/')) {
            return asset('storage/' . substr($path, 7));
        }

        return asset(ltrim($path, '/'));
    }

    private function activationUrl($token, $homeUrl = null)
    {
        $url = url('/api/activate/' . $token);
        return $homeUrl ? $this->appendQuery($url, ['home_url' => $homeUrl]) : $url;
    }

    private function passwordResetUrl(User $user, $token, Request $request = null)
    {
        $query = ['email' => $user->email];
        if ($request) {
            $loginUrl = $this->validatedAppUrl($request->input('login_url'));
            if ($loginUrl) {
                $query['login_url'] = $loginUrl;
            }
        }

        return url('/api/password-reset/' . $token . '?' . http_build_query($query));
    }

    private function passwordChangeUrl($token)
    {
        return url('/api/change-password/confirm/' . $token);
    }

    private function loginUrl(Request $request)
    {
        return $this->validatedAppUrl($request->query('login_url'))
            ?: env('NOTE_NOTY_LOGIN_URL', $request->getSchemeAndHttpHost() . '/login');
    }

    private function activationHomeUrl(Request $request)
    {
        return $this->validatedAppUrl($request->query('home_url'))
            ?: env('NOTE_NOTY_HOME_URL');
    }

    private function validatedAppUrl($url)
    {
        if (!is_string($url) || $url === '') {
            return null;
        }

        return preg_match('/^https?:\/\//i', $url) ? $url : null;
    }

    private function appendQuery($url, array $query)
    {
        $separator = strpos($url, '?') === false ? '?' : '&';
        return $url . $separator . http_build_query($query);
    }

    private function sendActivationMail(User $user, $homeUrl = null)
    {
        Mail::send('emails.notenoty_action', [
            'brand' => 'NoteNoty',
            'title' => 'Kích hoạt tài khoản của bạn',
            'hello' => 'Xin chào ' . $user->name . ', chào mừng bạn đến với NoteNoty!',
            'body' => 'Vui lòng kích hoạt tài khoản để sử dụng đầy đủ các chức năng như chia sẻ ghi chú và đổi mật khẩu.',
            'buttonText' => 'Kích hoạt tài khoản',
            'actionUrl' => $this->activationUrl($user->activation_token, $homeUrl),
            'note' => 'Liên kết này dùng để xác nhận email đã đăng ký. Nếu bạn không tạo tài khoản NoteNoty, bạn có thể bỏ qua email này.',
        ], function ($message) use ($user) {
            $message->to($user->email, $user->name)
                ->subject('Kích hoạt tài khoản NoteNoty');
        });
    }

    private function sendPasswordResetMail(User $user, $token, Request $request = null)
    {
        Mail::send('emails.notenoty_action', [
            'brand' => 'NoteNoty',
            'title' => 'Khôi phục mật khẩu NoteNoty',
            'hello' => 'Xin chào ' . $user->name . ',',
            'body' => 'Bạn vừa yêu cầu khôi phục mật khẩu. Hãy nhấn nút bên dưới để đặt mật khẩu mới.',
            'buttonText' => 'Đặt lại mật khẩu',
            'actionUrl' => $this->passwordResetUrl($user, $token, $request),
            'note' => 'Liên kết khôi phục có hiệu lực trong 2 giờ. Nếu bạn không yêu cầu, hãy bỏ qua email này.',
        ], function ($message) use ($user) {
            $message->to($user->email, $user->name)
                ->subject('Khôi phục mật khẩu NoteNoty');
        });
    }

    private function sendPasswordChangeMail(User $user, $token)
    {
        Mail::send('emails.notenoty_action', [
            'brand' => 'NoteNoty',
            'title' => 'Tạo mật khẩu mới cho tài khoản',
            'hello' => 'Xin chào ' . $user->name . ',',
            'body' => 'Bạn vừa yêu cầu đổi mật khẩu tài khoản NoteNoty. Hãy nhấn nút bên dưới để mở trang tạo mật khẩu mới.',
            'buttonText' => 'Tạo mật khẩu mới',
            'actionUrl' => $this->passwordChangeUrl($token),
            'note' => 'Liên kết này có hiệu lực trong 2 giờ. Nếu bạn không thực hiện yêu cầu này, hãy bỏ qua email.',
        ], function ($message) use ($user) {
            $message->to($user->email, $user->name)
                ->subject('Tạo mật khẩu mới cho tài khoản NoteNoty');
        });
    }

    private function toMilliseconds($date)
    {
        if (!$date) {
            return null;
        }

        return strtotime($date) * 1000;
    }

    private function initials($name)
    {
        $parts = preg_split('/\s+/', trim($name));
        $letters = '';

        foreach (array_slice($parts, 0, 2) as $part) {
            $letters .= mb_strtoupper(mb_substr($part, 0, 1));
        }

        return $letters ?: 'NN';
    }

    private function labelColor($id)
    {
        $colors = ['#7d72cc', '#61c8a8', '#f29e67', '#d7b43a', '#d76e97', '#67a6db', '#ed6b6b', '#6a7c8f'];
        return $colors[$id % count($colors)];
    }

    private function validationError($validator)
    {
        return response()->json([
            'success' => false,
            'message' => $validator->errors()->first(),
            'errors' => $validator->errors(),
        ], 422);
    }

    private function unauthorized()
    {
        return response()->json(['success' => false, 'message' => 'Vui lòng đăng nhập lại.'], 401);
    }

    private function forbidden()
    {
        return response()->json(['success' => false, 'message' => 'Bạn không có quyền thao tác ghi chú này.'], 403);
    }
}
