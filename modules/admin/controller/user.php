<?php

use Admin\Etc\Controller;
use THCFrame\Registry\Registry;
use THCFrame\Request\RequestMethods;
use THCFrame\Events\Events as Event;

/**
 * Description of UserController
 *
 * @author Tomy
 */
class Admin_Controller_User extends Controller
{

    /**
     * 
     */
    public function login()
    {
        $this->_willRenderLayoutView = false;
        $view = $this->getActionView();

        if (RequestMethods::post('submitLogin')) {
            $email = RequestMethods::post('email');
            $password = RequestMethods::post('password');
            $error = false;

            if (empty($email)) {
                $view->set('email_error', 'Email not provided');
                $error = true;
            }

            if (empty($password)) {
                $view->set('password_error', 'Password not provided');
                $error = true;
            }

            if (!$error) {
                try {
                    $security = Registry::get('security');
                    $status = $security->authenticate($email, $password);

                    if ($status) {
                        self::redirect('/admin/');
                    } else {
                        $view->set('account_error', 'Email address and/or password are incorrect');
                    }
                } catch (\Exception $e) {
                    if (ENV == 'dev') {
                        $view->set('account_error', 'Login system is down ' . $e->getMessage());
                    } else {
                        $view->set('account_error', 'Unknown error occured');
                    }
                }
            }
        }
    }

    /**
     * 
     */
    public function logout()
    {
        $security = Registry::get('security');
        $security->logout();
        self::redirect('/admin/');
    }

    /**
     * @before _secured, _admin
     */
    public function index()
    {
        $view = $this->getActionView();
        $security = Registry::get('security');

        $superAdmin = $security->isGranted('role_superadmin');

        $users = App_Model_User::all(
                        array('role <> ?' => 'role_superadmin'), 
                        array('id', 'firstname', 'lastname', 'email', 'role', 'active', 'created'), 
                        array('id' => 'asc')
        );

        $view->set('users', $users)
                ->set('superadmin', $superAdmin);
    }

    /**
     * @before _secured, _admin
     */
    public function add()
    {
        $security = Registry::get('security');
        $view = $this->getActionView();

        $errors = array();
        $superAdmin = $security->isGranted('role_superadmin');

        if (RequestMethods::post('submitAddUser')) {
            $this->checkToken();
            
            if (RequestMethods::post('password') !== RequestMethods::post('password2')) {
                $errors['password2'] = array('Paswords doesnt match');
            }

            $email = App_Model_User::first(array('email = ?' => RequestMethods::post('email')), array('email'));

            if ($email) {
                $errors['email'] = array('Email is already used');
            }

            $salt = $security->createSalt();
            $hash = $security->getSaltedHash(RequestMethods::post('password'), $salt);

            $user = new App_Model_User(array(
                'firstname' => RequestMethods::post('firstname'),
                'lastname' => RequestMethods::post('lastname'),
                'email' => RequestMethods::post('email'),
                'password' => $hash,
                'salt' => $salt,
                'role' => RequestMethods::post('role', 'role_publisher'),
            ));

            if (empty($errors) && $user->validate()) {
                $id = $user->save();

                Event::fire('admin.log', array('success', 'ID: ' . $id));
                $view->successMessage('Account has been successfully created');
                self::redirect('/admin/user/');
            } else {
                Event::fire('admin.log', array('fail'));
                var_dump($user->getErrors());
                $view->set('errors', $errors + $user->getErrors())
                        ->set('user', $user);
            }
        }

        $view->set('superadmin', $superAdmin);
    }

    /**
     * @before _secured, _admin
     * @param type $id
     */
    public function edit($id)
    {
        $view = $this->getActionView();
        $security = Registry::get('security');

        $errors = array();
        $superAdmin = $security->isGranted('role_superadmin');

        $user = App_Model_User::first(array('id = ?' => $id));

        if (NULL === $user) {
            $view->errorMessage('User not found');
            self::redirect('/admin/user/');
        } elseif ($user->role == 'role_superadmin' && !$superAdmin) {
            $view->errorMessage('You dont have permissions to update this user');
            self::redirect('/admin/user/');
        }

        if (RequestMethods::post('submitEditUser')) {
            $this->checkToken();
            
            if (RequestMethods::post('password') !== RequestMethods::post('password2')) {
                $errors['password2'] = array('Paswords doesnt match');
            }

            if (RequestMethods::post('email') != $user->email) {
                $email = App_Model_User::first(
                            array('email = ?' => RequestMethods::post('email', $user->email)), 
                            array('email')
                );
                
                if ($email) {
                    $errors['email'] = array('Email is already used');
                }
            }

            $pass = RequestMethods::post('password');
            
            if ($pass === null || $pass == '') {
                $salt = $user->getSalt();
                $hash = $user->getPassword();
            } else {
                $salt = $security->createSalt();
                $hash = $security->getSaltedHash($pass, $salt);
            }

            $user->firstname = RequestMethods::post('firstname');
            $user->lastname = RequestMethods::post('lastname');
            $user->email = RequestMethods::post('email');
            $user->password = $hash;
            $user->salt = $salt;
            $user->role = RequestMethods::post('role');
            $user->active = RequestMethods::post('active');

            if (empty($errors) && $user->validate()) {
                $user->save();

                Event::fire('admin.log', array('success', 'ID: ' . $id));
                $view->successMessage('All changes were successfully saved');
                self::redirect('/admin/user/');
            } else {
                Event::fire('admin.log', array('fail', 'ID: ' . $id));
                $view->set('errors', $errors + $user->getErrors());
            }
        }

        $view->set('user', $user)
                ->set('superadmin', $superAdmin);
    }

    /**
     * 
     * @before _secured, _superadmin
     * @param type $id
     */
    public function delete($id)
    {
        $this->willRenderActionView = false;
        $this->willRenderLayoutView = false;
        $this->checkToken();

        $user = App_Model_User::first(array('id = ?' => $id));

        if (NULL === $user) {
            echo 'User not found';
        } else {
            if ($user->delete()) {
                Event::fire('admin.log', array('success', 'ID: ' . $id));
                echo 'ok';
            } else {
                Event::fire('admin.log', array('fail', 'ID: ' . $id));
                echo 'Unknown error eccured';
            }
        }
    }

}
