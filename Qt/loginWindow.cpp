#include "loginwindow.h"

LoginWindow::LoginWindow()
{
    m_email = new QLineEdit;
    m_password = new QLineEdit;
    m_password->setEchoMode(QLineEdit::Password);

    QFormLayout *loginInfoLayout = new QFormLayout;
    loginInfoLayout->addRow("&Email", m_email);
    loginInfoLayout->addRow("&Password", m_password);

    m_signIn = new QPushButton("&Sign In");
    m_createLogin = new QPushButton("&Create Login");
    m_quit = new QPushButton("&Quit");

    QHBoxLayout *buttonsLayout = new QHBoxLayout;
    buttonsLayout->setAlignment(Qt::AlignBottom);

    buttonsLayout->addWidget(m_signIn);
    buttonsLayout->addWidget(m_createLogin);
    buttonsLayout->addWidget(m_quit);

    QVBoxLayout *mainLayout = new QVBoxLayout;
    mainLayout->addLayout(loginInfoLayout);
    mainLayout->addLayout(buttonsLayout);

    setLayout(mainLayout);
    setWindowTitle("Filebox - Login");
    //resize(400, 450);

    connect(m_quit, SIGNAL(clicked()), qApp, SLOT(quit()));
    connect(m_signIn, SIGNAL(clicked()), this, SLOT(signIn()));
    connect(m_createLogin, SIGNAL(clicked()), this, SLOT(createLogin()));
}

void LoginWindow::signIn()
{
    if (m_email->text().isEmpty())
    {
        QMessageBox::critical(this, "Error", "Please enter your email");
        return;
    }
    if (m_password->text().isEmpty())
    {
        QMessageBox::critical(this, "Error", "Please enter your password");
        return;
    }

    try
    {
        std::string url = "http://vincentmm.com/filebox/api/connexion?";
        url += "email=";
        url += m_email->text().toUtf8().constData();
        url += "&pass=";
        url += md5(m_password->text().toUtf8().constData());

        http::Request request(url);

        // send a get request
        http::Response response = request.send("GET");
        std::string sResponse(reinterpret_cast<char*>(response.body.data()));

        if(sResponse != "true")
        {
            if(sResponse == "false")
                QMessageBox::critical(this, "Error", "Wrong password or email");
            else
                QMessageBox::critical(this, "Error", "An error occured, please try later");
            return;
        }
    }
    catch (const std::exception& e)
    {
        QMessageBox::critical(this, "Error", "Failed to contact the Filebox server, please try later");
        return;
    }

    MainWindow *mainWindow = new MainWindow();
    mainWindow->show();

    this->close();
}

void LoginWindow::createLogin()
{
    QUrl url("http://vincentmm.com/filebox/inscription");
    QDesktopServices::openUrl(url);
}
