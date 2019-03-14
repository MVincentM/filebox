#ifndef MAINWINDOW_H
#define MAINWINDOW_H

#include <QtWidgets>
#include <QDesktopServices>
#include <fstream>
#include <sstream>

#include "websocket.h"
#include "httprequest.hpp"

class MainWindow : public QWidget
{
Q_OBJECT

public:
    MainWindow(WebSocket* webSocket);

private slots:
   void changeLogin();
   void viewOnline();
   void selectFolder();
   void checkSync();
   void onResult(QNetworkReply*);

private:
   QLabel *m_status;
   QPushButton *m_viewOnline;

   QPushButton *m_selectFolder;

   QPushButton *m_changeLogin;
   QPushButton *m_quit;

   WebSocket* m_webSocket;
   QTimer* mTimer;

   QNetworkAccessManager *m_networkManager;
   QNetworkRequest m_request;
};

#endif // MAINWINDOW_H
