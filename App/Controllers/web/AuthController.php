<?

namespace App\controllers\web;

use App\Core\Controller;
use App\Core\Session;
use App\Core\View;
use App\Models\Role;
use App\Models\User;

class AuthController extends Controller
{
    protected View $view;

    public function __construct(View $view)
    {
        $this->view = $view;
        $this->view->layout('layouts.main');
        //show($this->view);
    }

    public function index()
    {       
        return $this->view->render('auth/login', [
            'title' => 'Welcome to My Framework'
        ]);
        exit;
    }

    public function registerForm()
    {
        $users = Session::get('user');
        $roles = Role::all();

        show($users);

        return $this->view->render('admin/users', [
            'title' => 'Welcome to My Framework',
            'user'  => $users ?? [],
            'roles' => $roles ?? []
        ]);
    }

    public function logout() {
        Session::destroy();
        return $this->view->render('auth/login');exit;
    }
}