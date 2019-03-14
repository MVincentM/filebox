#include "loginwindow.h"

LoginWindow::LoginWindow(WebSocket* webSocket)
{
    m_webSocket = webSocket;

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

    m_networkManager = new QNetworkAccessManager();
    connect(m_networkManager, SIGNAL(finished(QNetworkReply*)), this, SLOT(onResult(QNetworkReply*)));
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

    std::string url = "http://127.0.0.1:8000/api/connexion?";
    url += "email=";
    url += m_email->text().toUtf8().constData();
    url += "&pass=";
    url += m_password->text().toUtf8().constData();

    QUrl qurl(url.c_str());
    m_requestConnexion.setUrl(qurl);
    m_networkManager->get(m_requestConnexion);
}

void LoginWindow::onResult(QNetworkReply* reply)
{
    if (reply->error()) {
        QMessageBox::critical(this, "Error", reply->errorString());
        return;
    }

    QString answer = reply->readAll();
    std::string sResponse = answer.toUtf8().toStdString();
    sResponse = sResponse.substr(1, sResponse.size() - 2);
    //QMessageBox::critical(this, "Error", QString::fromStdString(sResponse));

    if(!m_logged)
    {
        if(sResponse[0] != 'b')
        {
            if(sResponse.substr(0,4) == "false")
                QMessageBox::critical(this, "Error", "Wrong password or email");
            else
                QMessageBox::critical(this, "Error", "An error occured, please try later");
            return;
        }
        else
        {
            m_logged = true;
            m_webSocket->setAuthKey(sResponse.substr(1, sResponse.find("\"") - 1));

            std::string url = "http://localhost:8000/api/get/racine?authkey=" + sResponse.substr(1, sResponse.find("\"") - 1);

            QUrl qurl(url.c_str());
            m_requestConnexion.setUrl(qurl);

            m_networkManager->get(m_requestConnexion);
        }
    }
    else
    {
        m_webSocket->setEmail(m_email->text().toUtf8().constData());
        m_webSocket->setPassword(m_password->text().toUtf8().constData());

        sResponse = sResponse.substr(1, sResponse.find("b") - 1);
        m_webSocket->setRootID(sResponse);

        MainWindow *mainWindow = new MainWindow(m_webSocket);
        mainWindow->show();

        this->close();
    }
}

void LoginWindow::createLogin()
{
    QUrl url("http://localhost:8000/inscription");
    QDesktopServices::openUrl(url);
}
