<?php

namespace michnaadam33\rbacConsole;

use Yii;
use yii\console\Controller;
use yii\helpers\Console;

/**
 * Class RbacController
 * @package console\controllerst
 */
class RbacController extends Controller
{
    public $init;

    private $auth;


    private $collection;

    /**
     * @var string
     */
    private $userClass;

    public function __construct($id, $module, $config = [])
    {
        parent::__construct($id, $module, $config);

        $this->auth = Yii::$app->authManager;

        if (isset(Yii::$app->components['rbac-console'])) {
            $this->collection = Yii::$app->components['rbac-console'];
        }
        $this->userClass = Yii::$app->user->identityClass;

    }

    /**
     * Init rbac from settings
     * @return int
     */
    public function actionInit()
    {
        try {
            if (!isset($this->collection)) {
                throw new \Exception("Set components settings!");
            }
            if ($this->confirm('Do you want to create new roles and delete the previous')) {
                $this->auth->removeAll();
                if (isset($this->collection['rule_hierarchy'])) {
                    $rules = $this->collection['rule_hierarchy'];
                    foreach ($rules as $ruleClass) {
                        $rule = new $ruleClass;
                        $this->auth->add($rule);
                        $this->stdout("Added rule: " . $rule->name . "\n", Console::FG_GREEN);
                    }
                }
                if (isset($this->collection['permission_hierarchy'])) {
                    $permissions = $this->collection['permission_hierarchy'];
                    foreach ($permissions as $permissionName) {
                        if (is_array($permissionName)) {
                            $permission = $this->auth->createPermission($permissionName['name']);
                            if (isset($permissionName['rule'])) {
                                $permission->ruleName = $permissionName['rule'];
                            }
                            $this->auth->add($permission);
                            if (isset($permissionName['children'])) {
                                foreach ($permissionName['children'] as $childPermissionName) {
                                    $childPermission = $this->auth->getPermission($childPermissionName);
                                    if ($childPermission == null) {
                                        throw new \Exception('Permission ' . $childPermissionName . ' not exist');
                                    }
                                    $this->auth->addChild($permission, $childPermission);
                                }
                            }
                            $this->stdout("Added permission: " . $permissionName['name'] . "\n", Console::FG_GREEN);
                        } else {
                            $permission = $this->auth->createPermission($permissionName);
                            $this->auth->add($permission);
                            $this->stdout("Added permission: " . $permissionName . "\n", Console::FG_GREEN);
                        }
                    }
                }
                if (isset($this->collection['role_hierarchy'])) {
                    $roles = $this->collection['role_hierarchy'];
                    foreach ($roles as $roleName) {
                        if (is_array($roleName)) {
                            $role = $this->auth->createRole($roleName['name']);
                            $this->auth->add($role);
                            if (isset($roleName['permissions'])) {
                                foreach ($roleName['permissions'] as $permissionsName) {
                                    $childPermission = $this->auth->getPermission($permissionsName);
                                    if ($childPermission == null) {
                                        throw new \Exception('Permission ' . $permissionsName . ' not exist');
                                    }
                                    $this->auth->addChild($role, $childPermission);
                                }
                            }
                            if (isset($roleName['children'])) {
                                foreach ($roleName['children'] as $childRoleName) {
                                    $childRole = $this->auth->getRole($childRoleName);
                                    if ($childRole == null) {
                                        throw new \Exception('Role ' . $childRoleName . ' not exist');
                                    }
                                    $this->auth->addChild($role, $childRole);
                                }
                            }
                            $this->stdout("Added role: " . $roleName['name'] . "\n", Console::FG_GREEN);
                        } else {
                            $role = $this->auth->createRole($roleName);
                            $this->auth->add($role);
                            $this->stdout("Added role: " . $roleName . "\n", Console::FG_GREEN);
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            $this->stdout($e->getMessage() . "\n", Console::FG_RED);
            return Controller::EXIT_CODE_ERROR;
        }
    }



    public $by = 'user';

    /**
     * Common reset password of user
     * @param $username
     * @param $password
     * @return int
     */
    public function actionResetPassword($username, $password)
    {
        $user = $this->findUserByUsername($username);
        try {
            if (!isset($user)) {
                throw new \Exception("This user not exists!");
            }
            $user->password_hash = Yii::$app->getSecurity()->generatePasswordHash($password);
            $user->save();
            $this->stdout("Change password: " . $username . " to: " . $password . ".\n", Console::FG_GREEN);
            return Controller::EXIT_CODE_NORMAL;
        } catch (\Exception $e) {
            $this->stdout($e->getMessage() . "\n", Console::FG_RED);
            return Controller::EXIT_CODE_ERROR;
        }
    }

    /**
     * Common assign role to user
     * @param $roleName
     * @param $username
     * @return int
     */
    public function actionAssign($roleName, $username)
    {
        $role = $this->auth->getRole($roleName);
        $user = $this->findUserByUsername($username);
        try {
            if (!isset($role)) {
                throw new \Exception("This role not exists!");
            }
            if (!isset($user)) {
                throw new \Exception("This user not exists!");
            }
            $userId = $user->id;


            $this->auth->assign($role, $userId);
            $this->stdout("Assign role: " . $roleName . " to: " . $username . ".\n", Console::FG_GREEN);
            return Controller::EXIT_CODE_NORMAL;
        } catch (\Exception $e) {
            $this->stdout($e->getMessage() . "\n", Console::FG_RED);
            return Controller::EXIT_CODE_ERROR;
        }
    }

    /**
     * Common revoke role from user
     * @param $roleName
     * @param $username
     * @return int
     */
    public function actionRevoke($roleName, $username)
    {
        $role = $this->auth->getRole($roleName);
        $user = $this->findUserByUsername($username);
        try {
            if (!isset($role)) {
                throw new \Exception("This role not exists!");
            }
            if (!isset($user)) {
                throw new \Exception("This user not exists!");
            }
            $userId = $user->id;


            $this->auth->revoke($role, $userId);
            $this->stdout("Revoke role: " . $roleName . " of: " . $username . ".\n", Console::FG_GREEN);
            return Controller::EXIT_CODE_NORMAL;
        } catch (\Exception $e) {
            $this->stdout($e->getMessage() . "\n", Console::FG_RED);
            return Controller::EXIT_CODE_ERROR;
        }
    }

    /**
     * Show all roles
     * @return int
     */
    public function actionShowAllRoles()
    {
        try {
            $roles = $this->auth->getRoles();
            $rolesString = "\n";
            foreach ($roles as $key => $role) {
                $rolesString .= " - " . $key . "\ndescription: " . $role->description . "\n\n";
            }

            $this->stdout("Roles: " . $rolesString . "\n", Console::FG_GREEN);
            return Controller::EXIT_CODE_NORMAL;
        } catch (\Exception $e) {
            $this->stdout($e->getMessage() . "\n", Console::FG_RED);
            return Controller::EXIT_CODE_ERROR;
        }
    }

    /**
     * Show all permissions
     * @return int
     */
    public function actionShowAllPermissions()
    {
        try {
            $permissions = $this->auth->getPermissions();
            $permissionsString = "\n";
            foreach ($permissions as $key => $permission) {
                $permissionsString .= " - " . $key . "\ndescription: " . $permission->description . "\n\n";
            }

            $this->stdout("Permissions: " . $permissionsString . "\n", Console::FG_GREEN);
            return Controller::EXIT_CODE_NORMAL;
        } catch (\Exception $e) {
            $this->stdout($e->getMessage() . "\n", Console::FG_RED);
            return Controller::EXIT_CODE_ERROR;
        }
    }

    /**
     * Common show all roles of user
     * @param $username
     * @return int
     */
    public function actionShowRole($username)
    {
        $user = $this->findUserByUsername($username);
        try {
            if (!isset($user)) {
                throw new \Exception("This user not exists!");
            }

            $roles = $this->auth->getAssignments($user->id);
            $rolesString = "\n";
            foreach ($roles as $key => $role) {
                $rolesString .= " - " . $key . "\n";
            }

            $this->stdout("User: " . $username . " has roles: " . $rolesString . "\n", Console::FG_GREEN);
            return Controller::EXIT_CODE_NORMAL;
        } catch (\Exception $e) {
            $this->stdout($e->getMessage() . "\n", Console::FG_RED);
            return Controller::EXIT_CODE_ERROR;
        }
    }

    /**
     * Common show all permission from user or role
     * @param $name
     * @return int
     */
    public function actionShowPermission($name)
    {
        try {
            if ($this->by == "user") {
                $user = $this->findUserByUsername($name);
                if (!isset($user)) {
                    throw new \Exception("This user not exists!");
                }

                $permissions = $this->auth->getPermissionsByUser($user->id);
                $permissionsString = "\n";
                foreach ($permissions as $key => $role) {
                    $permissionsString .= " - " . $key . "\n";
                }
                if (count($permissions) != 0) {
                    $this->stdout("User: " . $name . " has permissions: " . $permissionsString . "\n", Console::FG_GREEN);
                } else {
                    $this->stdout("User: " . $name . " haven't any permissions.\n", Console::FG_GREEN);
                }
                return Controller::EXIT_CODE_NORMAL;
            } else if ($this->by == "role") {

                $role = $this->auth->getRole($name);
                if (!isset($role)) {
                    throw new \Exception("This role not exists!");
                }
                $permissions = $this->auth->getPermissionsByRole($name);
                $permissionsString = "\n";
                foreach ($permissions as $key => $role) {
                    $permissionsString .= " - " . $key . "\n";
                }

                if (count($permissions) != 0) {
                    $this->stdout("Role: " . $name . " has permissions: " . $permissionsString . "\n", Console::FG_GREEN);
                } else {
                    $this->stdout("Role: " . $name . " haven't any permissions.\n", Console::FG_GREEN);
                }
                return Controller::EXIT_CODE_NORMAL;
            } else {
                throw new \Exception("Unrecognized value by!");
            }
        } catch (\Exception $e) {
            $this->stdout($e->getMessage() . "\n", Console::FG_RED);
            return Controller::EXIT_CODE_ERROR;
        }
    }

    /**
     * Remove permission child from user or role.
     * @param $parentName
     * @param $childName
     * @return int
     */
    public function actionRemoveChildPermission($parentName, $childName)
    {
        try {
            if ($this->by == "user") {
                $parent = $this->findUserByUsername($parentName);
                if (!isset($parent)) {
                    throw new \Exception($parentName . " user not exists!");
                }
            } else if ($this->by == "role") {

                $parent = $this->auth->getRole($parentName);
                if (!isset($parent)) {
                    throw new \Exception($parentName . " role not exists!");
                }
            } else {
                throw new \Exception("Unrecognized value by!");
            }

            $child = $this->auth->getPermission($childName);
            if (!isset($child)) {
                throw new \Exception($childName . " permission not exists!");
            }

            $this->auth->removeChild($parent, $child);

            $this->stdout("Permission: " . $childName . " remove from: " . $parentName . "\n", Console::FG_GREEN);
            return Controller::EXIT_CODE_NORMAL;
        } catch (\Exception $e) {
            $this->stdout($e->getMessage() . "\n", Console::FG_RED);
            return Controller::EXIT_CODE_ERROR;
        }
    }

    /**
     * Remove role child from user or role
     * @param $parentName
     * @param $childName
     * @return int
     */
    public function actionRemoveChildRole($parentName, $childName)
    {
        try {

            $parent = $this->auth->getRole($parentName);
            if (!isset($parent)) {
                throw new \Exception($parentName . " role not exists!");
            }


            $child = $this->auth->getRole($childName);
            if (!isset($child)) {
                throw new \Exception($childName . " role not exists!");
            }

            $this->auth->removeChild($parent, $child);

            $this->stdout("Role: " . $childName . " remove from: " . $parentName . "\n", Console::FG_GREEN);
            return Controller::EXIT_CODE_NORMAL;
        } catch (\Exception $e) {
            $this->stdout($e->getMessage() . "\n", Console::FG_RED);
            return Controller::EXIT_CODE_ERROR;
        }
    }

    /**
     * Add child role to role.
     * @param $parentName
     * @param $childName
     * @return mixed
     */
    public function actionAddChildRole($parentName, $childName)
    {
        try {

            $parent = $this->auth->getRole($parentName);
            if (!isset($parent)) {
                throw new \Exception($parentName . " role not exists!");
            }


            $child = $this->auth->getRole($childName);
            if (!isset($child)) {
                throw new \Exception($childName . " role not exists!");
            }

            $this->auth->addChild($parent, $child);

            $this->stdout("Role: " . $childName . " add to: " . $parentName . "\n", Console::FG_GREEN);
            return Controller::EXIT_CODE_NORMAL;
        } catch (\Exception $e) {
            $this->stdout($e->getMessage() . "\n", Console::FG_RED);
            return Controller::EXIT_CODE_ERROR;
        }
    }

    /**
     * Add child permission to user or role
     * @param $parentName
     * @param $childName
     * @return mixed
     */
    public function actionAddChildPermission($parentName, $childName)
    {
        try {
            if ($this->by == "user") {
                $parent = $this->findUserByUsername($parentName);
                if (!isset($parent)) {
                    throw new \Exception($parentName . " user not exists!");
                }
            } else if ($this->by == "role") {

                $parent = $this->auth->getRole($parentName);
                if (!isset($parent)) {
                    throw new \Exception($parentName . " role not exists!");
                }
            } else {
                throw new \Exception("Unrecognized value by!");
            }

            $child = $this->auth->getPermission($childName);
            if (!isset($child)) {
                throw new \Exception($childName . " permission not exists!");
            }

            $this->auth->addChild($parent, $child);

            $this->stdout("Permission: " . $childName . " add to: " . $parentName . "\n", Console::FG_GREEN);
            return Controller::EXIT_CODE_NORMAL;
        } catch (\Exception $e) {
            $this->stdout($e->getMessage() . "\n", Console::FG_RED);
            return Controller::EXIT_CODE_ERROR;
        }
    }

    /**
     * Common create role.
     * @param $name
     * @param string $description
     * @return int
     */
    public function actionCreateRole($name, $description = "")
    {
        try {
            $role = $this->auth->createRole($name);
            $role->description = $description;
            $this->auth->add($role);

            $this->stdout("Role: " . $name . " created.\n", Console::FG_GREEN);
            return Controller::EXIT_CODE_NORMAL;
        } catch (\Exception $e) {
            $this->stdout($e->getMessage() . "\n", Console::FG_RED);
            return Controller::EXIT_CODE_ERROR;
        }
    }

    /**
     * Common create permission
     * @param $name
     * @param string $description
     * @return int
     */
    public function actionCreatePermission($name, $description = "")
    {
        try {
            $permission = $this->auth->createPermission($name);
            $permission->description = $description;
            $this->auth->add($permission);

            $this->stdout("Permission: " . $name . " created.\n", Console::FG_GREEN);
            return Controller::EXIT_CODE_NORMAL;
        } catch (\Exception $e) {
            $this->stdout($e->getMessage() . "\n", Console::FG_RED);
            return Controller::EXIT_CODE_ERROR;
        }
    }

    public function actionRemoveRole($name)
    {
        try {
            $role = $this->auth->getRole($name);
            if (!isset($role)) {
                throw new \Exception("This role not exists!");
            }
            $this->auth->remove($role);

            $this->stdout("Role: " . $name . " removed.\n", Console::FG_GREEN);
            return Controller::EXIT_CODE_NORMAL;
        } catch (\Exception $e) {
            $this->stdout($e->getMessage() . "\n", Console::FG_RED);
            return Controller::EXIT_CODE_ERROR;
        }
    }

    public function actionRemovePermission($name)
    {
        try {
            $permission = $this->auth->getPermission($name);
            if (!isset($permission)) {
                throw new \Exception("This permission not exists!");
            }

            $this->auth->remove($permission);
        } catch (\Exception $e) {
            $this->stdout($e->getMessage() . "\n", Console::FG_RED);
            return Controller::EXIT_CODE_ERROR;
        }
    }

    public function options($actionID)
    {
        $actions = ['show-permission', 'remove-child-permission', 'add-child-permission'];
        $ret = (in_array($actionID, $actions)) ? ['by'] : [];

        return array_merge(parent::options($actionID), $ret);
    }

    private function findUserByUsername($username){
        return call_user_func(array($this->userClass, '::findByUsername'), $username);
    }

}