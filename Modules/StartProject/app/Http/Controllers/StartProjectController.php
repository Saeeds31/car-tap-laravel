<?php

namespace Modules\StartProject\Http\Controllers;

use App\Http\Controllers\Controller;
use Modules\Users\Models\Permission;
use Modules\Users\Models\Role;
use Modules\Users\Models\User;

class StartProjectController extends Controller
{
    public function startproject()
    {
        $user = User::create([
            'full_name' => 'super admin',
            'mobile' => '09113894304',
            'national_code' => '2110628766',
            'marital_status' => true,
            'referral_code' => 'super123',
        ]);
        $roleSuperAdmin = Role::create([
            'name' => 'سوپر ادمین',
            'is_system' => true,
            'slug' => 'superAdmin',
            'supervisor' => true
        ]);

        Role::create([
            'name' => 'مشتری',
            'is_system' => true,
            'slug' => 'customer',
            'supervisor' => false
        ]);
        $user->roles()->sync([$roleSuperAdmin]);
        return response()->json(['message' => 'تنظیمات اولیه انجام شد پرمیژن ها را اجرا کنید']);
    }
    public function setSuperAdminPermissions()
    {
        $superAdminRole = Role::where('slug', 'superAdmin')->first();

        if (!$superAdminRole) {
            throw new \Exception('نقش superAdmin پیدا نشد. لطفاً ابتدا آن را ایجاد کنید.');
        }

        $allPermissions = Permission::all();
        if ($allPermissions->isEmpty()) {
            throw new \Exception('هیچ پرمیژنی در دیتابیس وجود ندارد.');
        }
        $superAdminRole->permissions()->syncWithoutDetaching($allPermissions->pluck('id')->toArray());
        return response()->json(['message' => "همه نقش ها به سوپر ادمین اختصاص یافت", 'success' => true]);
    }
    public function setPermissionTable()
    {
        $models = [
            'ArticleCategory'  => 'دسته بندی مقاله',
            'Article'      => 'مقاله',
            'Category'      => 'دسته بندی',
            'Banner'       => 'بنر',
            'Brand'       => 'برند',
            'Comment'      => 'کامنت',
            'City'   => 'شهر',
            'Province'   => 'استان',
            'Menu'   => 'منو',
            'Setting'   => 'تنظیمات',
            'Slider'   => 'اسلایدر',
            'Role'   => 'نقش',
            'User'   => 'کاربران',
            'team'   => 'تیم',
            'Wallet'   => 'کیف پول',
            'WalletTransaction'   => 'تراکنش کیف پول',
            'manager'   => 'مدیریت',
            'receipts'   => 'رسید',
            'CarRequest'   => 'درخواست خودرو',
            'Car'   => 'ماشین',
            'SalesPlan'   => 'طرح فروش',
            'Specification'   => 'مشخصات',
            'specification_group'   => 'گروه مشخصات',
        ];
        $actions = [
            'view'   => 'مشاهده',
            'store'  => 'ثبت',
            'update' => 'ویرایش',
            'delete' => 'حذف',

        ];
        foreach ($models as $model => $persianName) {
            $modelLower = strtolower($model);
            foreach ($actions as $action => $actionLabel) {
                Permission::updateOrCreate(
                    ['name' => "{$modelLower}_{$action}"],
                    ['label' => "{$actionLabel} {$persianName}"]
                );
            }
        }
        $others = [
            'dashboard_view' => 'داشبورد',
            'salesplan_cars' => 'داشبورد',
            'sendsms_view' => 'مشاهده پیامک های ارسال شده ',
            'sendsms_create' => 'ارسال پیامک',
            'report_users' => 'گزارش کاربران',
            'report_sales' => 'گزارش فروش',
            'comment_blogs' => 'کامنت مقالات',
            'notification_content' => 'اعلان محتوا',
            'notification_car' => 'اعلان ماشین ها',
            'notification_users' => 'اعلان کاربران',
            'notification_finance' => 'اعلان مالی',
            'notification_saleplan' => 'اعلان طرح ها',
            'role_permission' => 'دسترسی نقش'
        ];
        foreach ($others as $pername => $perPersianName) {
            Permission::updateOrCreate(
                ['name' => $pername],
                ['label' => $perPersianName]
            );
        }
        return response()->json(['message' => "همه دسترسی ها بروز رسانی شد", 'success' => true]);
    }
}
