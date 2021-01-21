<?php

namespace App\Http\Controllers\admin;

use Illuminate\Http\Request;
use App\Http\Requests\HrLoanRequest;
use App\Http\Requests\HrLoanPaymentRequest;
use App\Http\Controllers\Controller;
use App\Model\HrLoan;
use App\Model\HrLoanPayment;
use Redirect;
use Session;
use DB;
use Auth;
use App\Model\Admin;

class HrLoanPaymentController extends Controller
{

    public function __construct()
    {
        # check if admin login
        $this->middleware('auth:admin');
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create($id)
    {
        //pay the loan
        $strTitle = 'Refund Loan';
        $objLoan = HrLoan::find($id);
        $TotalLoan = $objLoan->fldAmount; //Total loan gets from fldAmount in loan Table
        $TotalPayment = HrLoanPayment::where('fkLoanID',$objLoan->pkLoanID)
        ->sum('fldAmount'); //Total Payment gets from fldAmount in loanPayment Table
        $RemainAmount = $TotalLoan - $TotalPayment; // The Rest of Amount
        $arrPaymentsLoans = HrLoanPayment::where('fkLoanID',$objLoan->pkLoanID)->get();
        return view('admin.hr_loans_payments.create',compact('strTitle','objLoan' , 'TotalLoan' , 'TotalPayment' , 'RemainAmount' , 'arrPaymentsLoans')); 
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(HrLoanPaymentRequest $request,$id)
    {        
        try {
            #insert
            $objLoan = HrLoan::find($id);
            $TotalLoan = $objLoan->fldAmount;
            $TotalPayment = HrLoanPayment::where('fkLoanID',$objLoan->pkLoanID)->sum('fldAmount');
            $RemainAmount = $TotalLoan - $TotalPayment;
            //if the Amount of HrLoanPaymentRequest  is Greater Than the rest of Amount
            //if 50 > (40 the rest of amount) ERROR
            if($request->input('fldAmount') > $RemainAmount){
                session()->flash('error' ,'Paid Amount Greater than Remainning');
                return Redirect::back();

            }
            //if the Amount of HrLoanPaymentRequest  is Equal Than the rest of Amount
            //if 40 == (40 the rest of amount)
            if($request->input('fldAmount') == $RemainAmount){
                $objLoanPayment = new HrLoanPayment();
                $objLoanPayment->fldAmount = $request->input('fldAmount');
                $objLoanPayment->fkLoanID = $objLoan->pkLoanID;
                $objLoanPayment->fldDate = $request->input('fldDate');
                $objLoanPayment->fkCreatedByUserID = Auth::user()->id;
                $objLoanPayment->save();
                $objLoan->fldStatus = HrLoan::PAID;
                $objLoan->save();
            }
            //if the Amount of HrLoanPaymentRequest  is less Than the rest of Amount
            // 30 < (40 the rest of amount)
            if($request->input('fldAmount') < $RemainAmount){
                $objLoanPayment = new HrLoanPayment();
                $objLoanPayment->fldAmount = $request->input('fldAmount');
                $objLoanPayment->fkLoanID = $objLoan->pkLoanID;
                $objLoanPayment->fldDate = $request->input('fldDate');
                $objLoanPayment->fkCreatedByUserID = Auth::user()->id;
                $objLoanPayment->save();                 
            }

            session()->flash('success' ,'Your Loan Payment has been added successfully.');
        } catch (\Exception $e) {
            session()->flash('error' ,'Please check data');
        }
        return Redirect::back();
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\HrLoanPayment  $hrLoanPayment
     * @return \Illuminate\Http\Response
     */
    // public function show(HrLoanPayment $hrLoanPayment)
    // {
    //     //
    // }

    // /**
    //  * Show the form for editing the specified resource.
    //  *
    //  * @param  \App\HrLoanPayment  $hrLoanPayment
    //  * @return \Illuminate\Http\Response
    //  */
    public function edit($id)
    {
       
        $strTitle = 'Edit Refund';
        $objLoanPayment = HrLoanPayment::find($id); 
        $pkLoanID = $objLoanPayment->fkLoanID;
        $objLoan = HrLoan::find($pkLoanID);
        $TotalLoan = $objLoan->fldAmount;
        $TotalPayment = HrLoanPayment::where('fkLoanID',$pkLoanID)->sum('fldAmount');
        $RemainAmount = $TotalLoan - $TotalPayment;
        
        if($TotalPayment == $TotalLoan && $RemainAmount == 0){
            // dd('here1');
            $objLoan->fldStatus = HrLoan::PAID;
            $objLoan->save();
        }else if($TotalPayment < $TotalLoan){
            // dd('here2');
            $objLoan->fldStatus = HrLoan::UNPAID;
            $objLoan->save();
        }
       

        return view('admin.hr_loans_payments.edit' ,compact('strTitle' , 'objLoanPayment' , 'objLoan' , 'TotalLoan' ,'TotalPayment' , 'RemainAmount'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\HrLoanPayment  $hrLoanPayment
     * @return \Illuminate\Http\Response
     */
    public function update(HrLoanPaymentRequest $request, $id)
    {
        try{
            $objLoanPayment = HrLoanPayment::find($id);
            $pkLoanID = $objLoanPayment->fkLoanID;
            $objLoan = HrLoan::find($pkLoanID);       
            $TotalLoan = $objLoan->fldAmount;
            $TotalPayment = HrLoanPayment::where('fkLoanID',$pkLoanID)->sum('fldAmount');
            $RemainAmount = $TotalLoan - $TotalPayment;
            //if the Amount of loanPayment request is Equal Than the rest of Amount
            if($request->input('fldAmount') == $RemainAmount){
                return Redirect::back();
            }
            //if the Amount of loanPayment request is greater Than the rest of Amount
            if($request->input('fldAmount') > $RemainAmount){
                $objLoanPayment->fldAmount = $request->input('fldAmount');
                $objLoanPayment->fldDate = $request->input('fldDate');
                $objLoanPayment->save();
                // dd($TotalPayment);
                // dd($request->input('fldAmount'));
                // dd($RemainAmount);
                // dd($request->input('fldAmount'));
            }
            // if the total payment  == the total loan  ?
            // the fldStatus in loan Table becomes PAID :
            // the fldStatus in loan Table becomes UNPAID     
            session()->flash('success' ,'Your Loan Payment has been Updated successfully.');
        } catch (\Exception $e) {
            session()->flash('error' ,'Please check data');
        }
        return Redirect::back();
    }

    // /**
    //  * Remove the specified resource from storage.
    //  *
    //  * @param  \App\HrLoanPayment  $hrLoanPayment
    //  * @return \Illuminate\Http\Response
    //  */
    public function destroy(HrLoanPaymentRequest $request,$id)
    {
        try { 
            //delete the loanPayment by id and update the fldStatus in loan Table into UNPAID
            $objLoanPayment = HrLoanPayment::find($id);
            $pkLoanID = $objLoanPayment->fkLoanID;
            $objLoan = HrLoan::find($pkLoanID);
            $objLoan->fldStatus = HrLoan::UNPAID;
            $objLoan->save();
            $objLoanPayment->delete();
            Session::flash('success' , 'Successfully Deleted!');
         } catch (\Exception $e){
            Session()->flash('error' ,'Error In Deleted');
         }
         return Redirect::back();
    }
}
