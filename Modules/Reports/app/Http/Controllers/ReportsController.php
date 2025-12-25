<?php

namespace Modules\Reports\Http\Controllers;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Modules\CourseOrder\Models\CourseOrder;
use Modules\CourseOrder\Models\OrderResult;
use Modules\Orders\Models\Order;
use Modules\Products\Models\Product;
use Modules\Users\Models\User;

class ReportsController extends Controller
{


    public static function courseOrderDetailedReport(Request $request)
    {
        $query = CourseOrder::query()
            ->with(['user', 'course']);   // هر رابطه‌ای داری اضافه کن

        // فیلترهای مربوط به User
        $query->whereHas('user', function ($q) use ($request) {
            if ($mobile = $request->get('mobile')) {
                $q->where('mobile', 'like', "%{$mobile}%");
            }
            if ($national_code = $request->get('national_code')) {
                $q->where('national_code', 'like', "%{$national_code}%");
            }
        });

        // فیلترهای مخصوص CourseOrder
        if ($pay_status = $request->get('pay_status')) {
            $query->where('pay_status', $pay_status);
        }
        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }
        if ($cost_from = $request->get('cost_from')) {
            $query->where('paid_cost', '>=', $cost_from);
        }
        if ($cost_to = $request->get('cost_to')) {
            $query->where('paid_cost', '<=', $cost_to);
        }
        if ($course_id = $request->get('course_id')) {
            $query->where('course_id', $course_id);
        }

        return $query->paginate(20);
    }
    public static function resultExamDetailedReport(Request $request)
    {
        $query = OrderResult::query()
            ->with([
                'courseOrder.user',
                'courseOrder.course'
            ]);

        // ---------------------------
        // فیلترهای مربوط به User
        // ---------------------------
        $query->whereHas('courseOrder.user', function ($q) use ($request) {

            if ($mobile = $request->get('mobile')) {
                $q->where('mobile', 'like', "%{$mobile}%");
            }
            if ($national_code = $request->get('national_code')) {
                $q->where('national_code', 'like', "%{$national_code}%");
            }
        });

        // ---------------------------
        // فیلترهای مربوط به CourseOrder
        // ---------------------------
        $query->whereHas('courseOrder', function ($q) use ($request) {

            if ($course_id = $request->get('course_id')) {
                $q->where('course_id', $course_id);
            }
        });

        // ---------------------------
        // فیلترهای مربوط به ResultExam
        // ---------------------------
        if ($status = $request->get('status')) { // قبول – رد شده…
            $query->where('status', $status);
        }

        if ($score_from = $request->get('score_from')) {
            $query->whereRaw("CAST(score AS DECIMAL(10,2)) >= ?", [$score_from]);
        }
        
        if ($score_to = $request->get('score_to')) {
            $query->whereRaw("CAST(score AS DECIMAL(10,2)) <= ?", [$score_to]);
        }
        

        return $query->paginate(20);
    }
}
