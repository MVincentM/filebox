#ifndef LOGINWINDOW_H
#define LOGINWINDOW_H

#include <QtWidgets>
#include <QString>
#include "mainwindow.h"
#include "httprequest.hpp"
#include "md5.h"

class LoginWindow : public QWidget
{
Q_OBJECT

public:
   LoginWindow();

private slots:
   void signIn();
   void createLogin();

private:
   QLineEdit *m_email;
   QLineEdit *m_password;

   QPushButton *m_signIn;
   QPushButton *m_createLogin;
   QPushButton *m_quit;
};

#endif // LOGINWINDOW_H
