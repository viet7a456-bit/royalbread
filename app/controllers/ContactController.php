<?php

declare(strict_types=1);

class ContactController extends Controller
{
    public function index(): void
    {
        $settingModel = new Setting();

        $this->render('contact/index', [
            'settings' => $settingModel->all(),
        ]);
    }

    public function store(): void
    {
        $name = trim($_POST['customer_name'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $subject = trim($_POST['subject'] ?? '');
        $message = trim($_POST['message'] ?? '');
        $contactTime = trim($_POST['contact_time'] ?? '');

        Session::setOld($_POST, 'contact');

        if ($name === '' || $phone === '' || $subject === '' || $message === '') {
            Session::flash('error', 'Vui lòng nhập đầy đủ họ tên, thông tin liên hệ, chủ đề và nội dung.');
            $this->redirectTo('contact');
        }

        $messageModel = new Message();
        $messageModel->create([
            'customer_name' => $name,
            'phone' => $phone,
            'contact_time' => $contactTime,
            'subject' => $subject,
            'message' => $message,
        ]);

        Session::clearOld('contact');
        Session::flash('success', 'RoyalBread đã nhận thông tin của bạn. Chúng tôi sẽ liên hệ sớm.');
        $this->redirectTo('contact');
    }
}
