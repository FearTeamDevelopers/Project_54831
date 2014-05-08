<?php

namespace THCFrame\Security;

use THCFrame\Core\Base as Base;
use THCFrame\Events\Events as Events;
use THCFrame\Registry\Registry as Registry;
use THCFrame\Security\Exception as Exception;
use THCFrame\Security\UserInterface;
use THCFrame\Security\AdvancedUserInterface;
use THCFrame\Security\RoleManager;

/**
 * Description of Security
 *
 * @author Tomy
 */
class Security extends Base
{

    /**
     * @read
     * @var type 
     */
    protected $_accessControl;

    /**
     * @read
     * @var type 
     */
    protected $_passwordEncoder;

    /**
     * @read
     * @var type 
     */
    protected $_roleManager;

    /**
     * @read
     * @var type
     */
    protected $_acl;

    /**
     * @read
     * @var type 
     */
    protected $_loginCredentials = array();

    /**
     * @read
     * @var type 
     */
    protected $_user = null;

    /**
     * 
     * @param type $method
     * @return \THCFrame\Security\Exception\Implementation
     */
    protected function _getImplementationException($method)
    {
        return new Exception\Implementation(sprintf('%s method not implemented', $method));
    }

    /**
     * 
     */
    public function initialize()
    {
        Events::fire('framework.security.initialize.before', array($this->accessControll));

        $configuration = Registry::get('config');

        if (!empty($configuration->security->default)) {
            $rolesOptions = (array) $configuration->security->default->roles;
            $this->_loginCredentials = $configuration->security->default->loginCredentials;
            $this->_passwordEncoder = $configuration->security->default->encoder;
            $this->_accessControl = $configuration->security->default->accessControl;
        } else {
            throw new \Exception('Error in configuration file');
        }

        if ($this->_accessControl == 'role_based') {
            $this->_roleManager = new RoleManager($rolesOptions);
        } elseif ($this->_accessControl == 'acl') {
            //$this->_acl = new Acl();
        } else {
            throw new Exception\Implementation('Access controll is not supported');
        }

        $session = Registry::get('session');
        $user = $session->get('authUser');

        if ($user) {
            $this->_user = $user;
            Events::fire('framework.security.initialize.user', array($user));
        }

        Events::fire('framework.security.initialize.after', array($this->accessControll));

        return $this;
    }

    /**
     * 
     * @param \THCFrame\Security\UserInterface $user
     */
    public function setUser(UserInterface $user)
    {
        @session_regenerate_id();

        $session = Registry::get('session');
        $session->set('authUser', $user)
                ->set('lastActive', time());

        $this->_user = $user;
    }

    /**
     * 
     * @return \THCFrame\Security\UserInterface
     */
    public function getUser()
    {
        return $this->_user;
    }

    /**
     * 
     */
    public function logout()
    {
        $session = Registry::get('session');
        $session->erase('authUser')
                ->erase('lastActive');

        $this->_user = NULL;
        @session_regenerate_id();
    }

    /**
     * 
     * @param type $value
     * @return type
     * @throws Exception\HashAlgorithm
     */
    public function getHash($value)
    {
        $salt = 'PIqC792XES6mbRoUpD0TFiej5';
        
        if ($value == '') {
            return '';
        } else {
            if (in_array($this->_passwordEncoder, hash_algos())) {
                return hash_hmac($this->_passwordEncoder, $value, $salt);
            } else {
                throw new Exception\HashAlgorithm(sprintf('Hash algorithm %s is not supported', $this->_passwordEncoder));
            }
        }
    }

    /**
     * 
     * @param type $requiredRole
     * @return boolean
     * @throws Exception\Role
     */
    public function isGranted($requiredRole)
    {
        if ($this->_user) {
            $userRole = strtolower($this->_user->getRole());
        } else {
            $userRole = 'role_guest';
        }

        $requiredRole = strtolower(trim($requiredRole));

        if (substr($requiredRole, 0, 5) != 'role_') {
            throw new Exception\Role(sprintf('Role %s is not valid', $requiredRole));
        } elseif (!$this->_roleManager->roleExist($requiredRole)) {
            throw new Exception\Role(sprintf('Role %s is not deffined', $requiredRole));
        } else {
            $userRoles = $this->_roleManager->getRole($userRole);

            if (NULL !== $userRoles) {
                if (in_array($requiredRole, $userRoles)) {
                    return true;
                } else {
                    return false;
                }
            } else {
                throw new Exception\Role(sprintf('User role %s is not valid role', $userRole));
            }
        }
    }

    /**
     * 
     * @param type $pass
     * @param type $pass2
     * @return type
     */
    public function comparePasswords($pass, $pass2)
    {
        return $this->getHash($pass) === $this->getHash($pass2);
    }

    /**
     * 
     * @param type $email
     * @param type $password
     * @return boolean
     * @throws Exception\UserInactive
     * @throws Exception\UserExpired
     * @throws Exception\UserPassExpired
     * @throws Exception\Implementation
     */
    public function authenticate($loginCredential, $password, $adminRequired = false)
    {
        $hash = $this->getHash($password);

        $user = \App_Model_User::first(array(
                    "{$this->_loginCredentials->login} = ?" => $loginCredential,
                    "{$this->_loginCredentials->pass} = ?" => $hash
        ));

        if (NULL !== $user) {
            unset($user->_password);
            if ($user instanceof AdvancedUserInterface) {
                if (!$user->isActive()) {
                    $message = 'User account is not active';
                    Events::fire('framework.security.authenticate.failure', array($user, $message));
                    throw new Exception\UserInactive($message);
                } elseif ($user->isExpired()) {
                    $message = 'User account has expired';
                    Events::fire('framework.security.authenticate.failure', array($user, $message));
                    throw new Exception\UserExpired($message);
                } elseif ($user->isPassExpired()) {
                    $message = 'User password has expired';
                    Events::fire('framework.security.authenticate.failure', array($user, $message));
                    throw new Exception\UserPassExpired($message);
                } else {
                    $user->setLastLogin();
                    $user->save();

                    $this->setUser($user);

                    if ($adminRequired) {
                        if ($this->isGranted('role_admin')) {
                            Events::fire('framework.security.authenticate.success', array($user));
                            return true;
                        } else {
                            $message = 'You dont have permission to access required content';
                            Events::fire('framework.security.authenticate.failure', array($user, $message));
                            $this->logout();
                            throw new Exception\Unauthorized($message);
                        }
                    } else {
                        Events::fire('framework.security.authenticate.success', array($user));
                        return true;
                    }
                }
            } elseif ($user instanceof UserInterface) {

                if (!$user->isActive()) {
                    $message = 'User account is not active';
                    Events::fire('framework.security.authenticate.failure', array($user, $message));
                    throw new Exception\UserInactive($message);
                } else {
                    $this->setUser($user);

                    if ($adminRequired) {
                        if ($this->isGranted('role_admin')) {
                            Events::fire('framework.security.authenticate.success', array($user));
                            return true;
                        } else {
                            $message = 'You dont have permission to access required content';
                            Events::fire('framework.security.authenticate.failure', array($user, $message));
                            $this->logout();
                            throw new Exception\Unauthorized($message);
                        }
                    } else {
                        Events::fire('framework.security.authenticate.success', array($user));
                        return true;
                    }
                }
            } else {
                throw new Exception\Implementation(sprintf('%s is not implementing UserInterface', get_class($user)));
            }
        } else {
            return false;
        }
    }

}
