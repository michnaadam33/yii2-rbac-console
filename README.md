# Rbac console

============

## This is common controller to Yii2 Rbac module


## Installation



The preferred way to install this extension is through [composer](http://getcomposer.org/download/).



Either run



```

php composer.phar require --prefer-dist michnaadam33/yii2-rbac-console "*"

```



or add



```

"michnaadam33/yii2-rbac-console": "*"

```



to the require section of your `composer.json` file.






## Read about RBAC
[http://www.yiiframework.com/doc-2.0/guide-security-authorization.html](http://www.yiiframework.com/doc-2.0/guide-security-authorization.html)

## Create init action
You can config a default role on yii2 config:
```
'components' => [
        'rbac-console' => [
            'class' => 'michnaadam33\rbacConsole\Collection',
            'rule_hierarchy' => [
                'common\rbac\AuthorPostRule'
            ],
           'permission_hierarchy' => [
                'createPost',
                'deletePost',
                [
                    'name' =>'deleteOwnPost',
                    'rule' => 'RULE_AUTHOR_POST',
                    'children' => ['deletePost']
                ],
           ],
           'role_hierarchy' => [
                'ROLE_GUEST',
                'ROLE_CHILD',
                'ROLE_USER',
                [
                     'name' =>'ROLE_ADMIN',
                     'children' =>['ROLE_USER'],
                     'permissions' => [
                          'createTravel',
                               'createPost',
                               'deletePost',
                     ]
                ],
           ],
        ...
]
```

When you run `yii rbac/init` will be five roles:

* ROLE_GUEST
* ROLE_CHILD
* ROLE_USER
* ROLE_ADMIN
* ROLE_SUPER_ADMIN

## Posible action:

Reset password.

    yii rbac/reset-password <username> <password>
Assign user.

    yii rbac/assign <rolename> <username>
Revoke user.

    yii rbac/revoke <rolename> <username>
    
Show all permissions

    yii rbac/show-all-permissions
Show all roles

    yii rbac/show-all-roles
Show all roles of user.

    yii rbac/show-role <username>
Show all permission from user or role.
  
    yii rbac/show-permission <name> [--by=role]
Remove permission child from user or role.

    yii rbac/remove-child-permission <parentName> <childName>[--by=role]
Remove role child from role.

    yii rbac/remove-child-role <parentName> childName>
Add child role to role.

    yii rbac/add-child-role <parentName> <childName>
Add child premission to user or role.

    yii rbac/add-child-permission <parentName> <childName> [--by=role]
Create role.

    yii rbac/create-role <name>
Create permission

    yii rbac/create-permission <name>
Remove role.

    yii rbac/remove-role <name>
Remove permission.

    yii rbac/remove-permission <name>
    
### License

And of course:

MIT: [LICENSE][license]

====

> Adam Michna
[http://symetrland.com](http://symetrland.com/)

[license]: ../master/LICENSE.md
