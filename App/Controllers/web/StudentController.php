<?php

namespace App\Controllers\Web;

use App\Core\Request;
use App\Core\Response;
use App\Core\View;
use App\Models\Student;
use App\Requests\StudentRequest;
use Database\ORM\Model;

class StudentController
{
    protected View $view;

    /**
     * StudentController constructor.
     *
     * @param View $view View engine for rendering web templates
     */
    public function __construct(View $view) {
        $this->view = $view;
        $this->view->layout('layouts.main');
    }

    /**
     * Renders the students list page.
     *
     * @return mixed Rendered view content
     */
    public function index(): mixed
    {
        //Auth::requirePermission('view_students');
        //$students = Student::all();
        return $this->view->render('students/index', ['students' => []]);
    }

    /**
     * Renders the student creation page.
     *
     * @return mixed Rendered view content
     */
    public function create(): mixed
    {
        $countries = (array)Model::select(['country_id', 'name'], 'countries');
        $classes = (array)Model::all('classes');

       $date = date('Y-m-d');
 
        $studentNo = Student::generateStudentNo($date);

        
        //show($countries[0]['name']);
        return $this->view->render('students/create', [
            'students' => [],
            'countries' => $countries,
            'classes'   => $classes,
            'student_no' => $studentNo
        ]);
    }

   /*  public function store(Request $request)
    {
        $data = $request->all();
        $errors = StudentRequest::validate($data);
        if ($errors) {
            return Response::view('students/create', ['errors' => $errors, 'old' => $data]);
        }

        Student::create($data);
        return header('Location: /students');
    }

    public function edit(Request $request, $id)
    {
        $student = Student::find($id);
        if (!$student) {
            http_response_code(404);
            echo "Student not found";
            exit;
        }
        return Response::view('students/edit', ['student' => $student]);
    }

    public function update(Request $request, $id)
    {
        $student = Student::find($id);
        if (!$student) {
            http_response_code(404);
            echo "Student not found";
            exit;
        }

        $data = $request->all();
        $errors = StudentRequest::validate($data, $id);
        if ($errors) {
            return Response::view('students/edit', ['errors' => $errors, 'student' => $student]);
        }

        $student->update($data);
        return header('Location: /students');
    }

    public function delete(Request $request, $id)
    {
        $student = Student::find($id);
        if ($student) {
            $student->delete();
        }
        return header('Location: /students');
    } */
}
