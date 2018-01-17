<?php

namespace App\Http\Controllers\Sentinel;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Sentinel;
use Redirect;
use Illuminate\Support\Facades\Mail;

class UserController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:user.create', [
            'only' => [
                'store'
            ]
        ]);

        $this->middleware('permission:user.view', [
            'only' => [
                'index'
            ]
        ]);

        $this->middleware('permission:user.delete', [
            'only' => [
                'destroy'
            ]
        ]);

        $this->middleware('permission:user.update', [
            'only' => [
                'update'
            ]
        ]);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $user = Sentinel::check();
        if (!!$user) {
            return view('sentinel.users', [
                'roles' => Sentinel::getRoleRepository()->all(),
                'users' => Sentinel::getUserRepository()->paginate(config("app.items_per_page"))
            ]);
        }
        /*
        return view('sentinel.users',
            [
                'role' => count($user->roles) == 0 ? 'guest' : $user->roles[0]->slug,
                'roles' => Sentinel::getRoleRepository()->all(),
                'users' => Sentinel::getUserRepository()->all()
            ]);
        */
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        // バリデーション
        $this->validate($request, [
            // nameは必須で、255文字まで
            'name' => 'required|max:255',
            // emailは必須で、emailの形式で、255文字までで、usersテーブル内でユニーク
            'email' => 'required|email|max:255|unique:users',
            // passwordは必須で、6文字以上255文字以下で、確認欄と一致する必要がある
            'password' => 'between:6,255|confirmed',
        ]);

        // パスワードが無指定の場合は、自動生成する
        $pass = $request->password;

        if (empty($pass)) {
            $pass = str_random(config('app.password_generate_length'));
        }

        // DBに登録
        $credentials = [
            'name' => $request['name'],
            'email' => $request['email'],
            'password' => $pass,
        ];

        $user = Sentinel::registerAndActivate($credentials);

        // ロールを設定する
        $roles = [];
        $rolenames = "";
        $allroles = Sentinel::getRoleRepository()->all();

        foreach ($allroles as $role) {
            if ($request['user_new_role_' . $role->id] == "on") {
                $role->users()->attach($user);
                if (mb_strlen($rolenames) > 0) {
                    $rolenames .= ", ";
                }
                $rolenames .= $role->name;
            }
        }

        // メールで送信する
        $this->sendMail([
            'toemail' => config('app.admin_email'),
            'toname' => config('app.admin_name'),
            'subject' => trans('sentinel.user_regist_subject'),
            'blade' => 'sentinel.emails.user-regist-done',
            'args' => [
                'name' => $request['name'],
                'email' => $request['email'],
                'password' => $pass,
                'roles' => $rolenames,
            ]
        ]);

        // メールを確認して、承認してからログインすることを表示するページへ
        return redirect('users')->with('info', trans('sentinel.user_regist_done'));
    }

    /**
     * Display the specified resource.
     *
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        // ユーザーを検索
        $user = Sentinel::findById($id);

        if (!$user) {
            // 手動でアクセスした場合はユーザーが見つからない可能性があるので、チェックをしておく
            return Redirect::back()->withInput()->withErrors(['user_not_found' => trans('sentinel.user_not_found')]);
        }

        // 更新したデータがあれば更新する
        $userid = 'user_' . $id . "_";

        $changed = [];
        if ((!empty($request[$userid . 'name'])) && ($user->name !== $request[$userid . 'name'])) {
            $changed['name_changed'] = $user->name . " > " . $request[$userid . 'name'];
            $user->name = $request[$userid . 'name'];
        }

        if ((!empty($request[$userid . 'email'])) && ($user->email !== $request[$userid . 'email'])) {
            $changed['email_changed'] = $user->email . " > " . $request[$userid . 'email'];
            $user->email = $request[$userid . 'email'];
        }

        if (count($changed) > 0) {
            $user->save();
        }

        // ロールのチェック
        $nowroles = "";
        foreach (Sentinel::getRoleRepository()->all() as $role) {
            $idxinrole = $userid . 'role_' . $role->id;
            $inrole = (!empty($request[$idxinrole] && ($request[$idxinrole] === "on")));
            $nowrole = $user->inRole($role->slug);
            if ($nowrole && !$inrole) {
                // ロールを外す
                $changed['role_detach' . $role->id] = $role->name . trans('sentinel.detach_role');
                $role->users()->detach($user);
            } else if (!$nowrole && $inrole) {
                // ロールを設定
                $changed['role_attach' . $role->id] = $role->name . trans('sentinel.attach_role');
                $role->users()->attach($user);
            }
        }

        // 結果を表示して戻る
        return Redirect::back()->withInput()->with(['info' => $changed]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        // ユーザーを検索
        $user = Sentinel::findById($id);
        if (!$user) {
            // 手動でアクセスした場合はユーザーが見つからない可能性があるので、チェックをしておく
            return Redirect::back()->withInput()->withErrors(['user_not_found' => trans('sentinel.user_not_found')]);
        }

        // 削除実行
        $user->delete();

        // 削除完了メッセージを添えて元のページに戻る
        return Redirect::back()->with(['info' => trans('sentinel.user_delete_done')]);
    }

    /**
     * 指定の内容でメールを送信する
     * @param array $params 送信データの連想配列
     * 'toemail' 宛先メールアドレス
     * 'toname' 宛先名
     * 'subject' メール件名
     * 'blade' 本文のテンプレート名
     * 'args' bladeに渡す連想配列
     */
    public function sendMail($params)
    {
        Mail::send($params['blade'], [
            'args' =>
                $params['args']],
            function ($m) use ($params) {
                $m->from(config('app.admin_email'), config('app.appname'));
                $m->to($params['toemail'], $params['toname'])->subject($params['subject']);
            });
    }
}
