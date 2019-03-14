#ifndef LOGINWINDOW_H
#define LOGINWINDOW_H

#include <QtWidgets>
#include <QString>
#include <QNetworkAccessManager>
#include "mainwindow.h"
#include "httprequest.hpp"
#include "md5.h"

class LoginWindow : public QWidget
{
Q_OBJECT

public:
   LoginWindow(WebSocket* webSocket);

private slots:
   void signIn();
   void createLogin();
   void onResult(QNetworkReply*);

private:
   QLineEdit *m_email;
   QLineEdit *m_password;

   QPushButton *m_signIn;
   QPushButton *m_createLogin;
   QPushButton *m_quit;

   WebSocket* m_webSocket;

   QNetworkAccessManager *m_networkManager;
   QNetworkRequest m_requestConnexion;

   bool m_logged = false;
};

#endif // LOGINWINDOW_H
