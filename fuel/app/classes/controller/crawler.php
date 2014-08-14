<?php

class Controller_Crawler extends Controller
{

    /**
     * The basic welcome message
     *
     * @access  public
     * @return  Response
     */
    public function action_index() {
        die('x');
        return Response::forge(View::forge('welcome/index'));
    }
}

