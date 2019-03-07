#ifndef MAINWINDOW_H
#define MAINWINDOW_H

#include <QtWidgets>
#include <QDesktopServices>
#include <fstream>
#include <sstream>

class MainWindow : public QWidget
{
Q_OBJECT

public:
    MainWindow();

private slots:
   void changeLogin();
   void viewOnline();
   void selectFolder();

private:
   QLabel *m_status;
   QPushButton *m_viewOnline;

   QPushButton *m_selectFolder;

   QPushButton *m_changeLogin;
   QPushButton *m_quit;
};

#endif // MAINWINDOW_H
