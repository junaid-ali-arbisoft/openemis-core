<?php

namespace App\Repositories;

use App\Http\Controllers\Controller;
use App\Models\AssessmentGradingOptions;
use App\Models\AssessmentGradingTypes;
use App\Models\AssessmentItem;
use App\Models\AssessmentPeriod;
use App\Models\Assessments;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use JWTAuth;

class AssessmentRepository extends Controller
{


    public function getEducationGradeList($request)
    {
        try {

            $params = $request->all();

            // $assessments = Assessments::get();
            // return $assessments;
            $assessments = new Assessments();
            $limit = config('constants.defaultPaginateLimit');

            if(isset($params['limit'])){
                $limit = $params['limit'];
            }
            $list = $assessments->paginate($limit);
            
            return $list;
            
            } catch (\Exception $e) {
            Log::error(
                'Failed to get Assessment Education Grade List.',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Failed to get Assessment Education Grade List.');
        }
    }

    public function getAssessmentItemList($request)
    {
        try {

            $params = $request->all();

            $assessmentItem = AssessmentItem::with('educationSubjects');
            if(isset($params['order'])){
                $orderBy = $params['order_by']??"ASC";
                $col = $params['order'];
                $assessmentItem = $assessmentItem->orderBy($col, $orderBy);
            }
            $limit = config('constants.defaultPaginateLimit');

            if(isset($params['limit'])){
                $limit = $params['limit'];
            }

            $list = $assessmentItem->paginate($limit)->toArray();
            
            return $list;

            } catch (\Exception $e) {
            Log::error(
                'Failed to get Assessment Item List.',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Failed to get Assessment Item List.');
        }
    }

    public function getAssessmentPeriodList($request)
    {
        try {

            $params = $request->all();
            $assessmentPeriod = AssessmentPeriod::with('assessments');
            if(isset($params['order'])){
                $orderBy = $params['order_by']??"ASC";
                $col = $params['order'];
                $assessmentPeriod = $assessmentPeriod->orderBy($col, $orderBy);
            }
            $limit = config('constants.defaultPaginateLimit');

            if(isset($params['limit'])){
                $limit = $params['limit'];
            }

            $list = $assessmentPeriod->paginate($limit)->toArray();
            
            return $list;

            } catch (\Exception $e) {
            Log::error(
                'Failed to get Assessment Period List.',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Failed to get Assessment Period List.');
        }
    }

    public function getAssessmentItemGradingTypeList($request)
    {
        try {

            $params = $request->all();
            $assessmentGradingTypes = AssessmentGradingTypes::with('assessmentGradingOptions');
            if(isset($params['order'])){
                $orderBy = $params['order_by']??"ASC";
                $col = $params['order'];
                $assessmentGradingTypes = $assessmentGradingTypes->orderBy($col, $orderBy);
            }
            $limit = config('constants.defaultPaginateLimit');

            if(isset($params['limit'])){
                $limit = $params['limit'];
            }

            $list = $assessmentGradingTypes->paginate($limit)->toArray();
            
            return $list;

            } catch (\Exception $e) {
            Log::error(
                'Failed to get Assessment Item Grading Type List.',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Failed to get Assessment Item Grading Type List.');
        }
    }

    public function getAssessmentGradingOptionList($request)
    {
        try {

            $params = $request->all();

            $assessmentGradingOptions = new AssessmentGradingOptions();
            $limit = config('constants.defaultPaginateLimit');

            if(isset($params['limit'])){
                $limit = $params['limit'];
            }
            $list = $assessmentGradingOptions->paginate($limit);
            
            return $list;
            
            } catch (\Exception $e) {
            Log::error(
                'Failed to get Assessment Grading Option List.',
                ['message'=> $e->getMessage(), 'trace' => $e->getTraceAsString()]
            );

            return $this->sendErrorResponse('Failed to get Assessment Grading Option List.');
        }
    }

}


        
