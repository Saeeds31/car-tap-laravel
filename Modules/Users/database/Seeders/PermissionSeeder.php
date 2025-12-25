<?php

namespace Modules\Users\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Users\Models\Permission;

class PermissionSeeder extends Seeder
{
    public function run()
    {

        $models = [
            'Address'   => 'آدرس',
            'ArticleCategory'  => 'دسته بندی مقاله',
            'Article'      => 'مقاله',
            'Banner'       => 'بنر',
            'Brochure'   => 'جزوه',
            'Comment'      => 'کامنت',
            'Course'   => 'دوره',
            'Category'   => 'دسته بندی دوره',
            'CourseOrder'   => 'فروش دوره',
            'OrderResult'   => 'نتیجه دوره',
            'Exam'   => 'آزمون',
            'City'   => 'شهر',
            'Province'   => 'استان',
            'Menu'   => 'منو',
            'Message'   => 'پیغام',
            'Response'   => 'پاسخ',
            'PaymentTransactions'   => 'تراکنش های پرداخت',
            'Question'   => 'سوال',
            'IdentityDocument'   => 'اسناد شخصی',
            'ImportantDocument'   => 'اسناد مهم',
            'PhysicalCharacteristics'   => 'مشخصات فیزیکی',
            'Register'   => 'ثبت نام',
            'Setting'   => 'تنظیمات',
            'Slider'   => 'اسلایدر',
            'Role'   => 'نقش',
            'User'   => 'کاربران',
            'Validity'   => 'اعتبار کاربر',
            'Wallet'   => 'کیف پول',
            'WalletTransaction'   => 'تراکنش کیف پول',
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
    }
}
