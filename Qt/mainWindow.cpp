#include "mainwindow.h"

MainWindow::MainWindow()
{
    m_viewOnline = new QPushButton("&View files online");
    m_status = new QLabel("Everything is working !");
    m_status->setAlignment(Qt::AlignHCenter);
    m_status->setStyleSheet("QLabel { color : green; }");
    m_status->setWordWrap(true);
    m_selectFolder = new QPushButton("Select &Folder");

    QVBoxLayout *quickButtons = new QVBoxLayout;
    quickButtons->addWidget(m_status);
    quickButtons->addWidget(m_viewOnline);
    quickButtons->addWidget(m_selectFolder);

    m_changeLogin = new QPushButton("Change &Login");
    m_quit = new QPushButton("&Quit");

    QHBoxLayout *buttonsLayout = new QHBoxLayout;
    buttonsLayout->setAlignment(Qt::AlignBottom);

    buttonsLayout->addWidget(m_changeLogin);
    buttonsLayout->addWidget(m_quit);

    QVBoxLayout *mainLayout = new QVBoxLayout;
    mainLayout->addLayout(quickButtons);
    mainLayout->addLayout(buttonsLayout);

    setLayout(mainLayout);
    setWindowTitle("Filebox - Main");

    connect(m_quit, SIGNAL(clicked()), qApp, SLOT(quit()));
    connect(m_viewOnline, SIGNAL(clicked()), this, SLOT(viewOnline()));
    connect(m_changeLogin, SIGNAL(clicked()), this, SLOT(changeLogin()));
    connect(m_selectFolder, SIGNAL(clicked()), this, SLOT(selectFolder()));

    std::ifstream infile("folderToSync.txt");
    std::string line;
    int i = 0;
    while (std::getline(infile, line))
    {
        m_status->setText("Your are correctly synchronising the folder : " + QString(line.c_str()));
        ++i;
    }
    if(i == 0)
    {
         m_status->setText("You're correctly connected to Filebox but no folder to synchronised has been set");
         m_status->setStyleSheet("QLabel { color : orange; }");
    }
}

void MainWindow::viewOnline()
{
    QUrl url("http://vincentmm.com/filebox/fichiers");
    QDesktopServices::openUrl(url);
}

void MainWindow::changeLogin()
{
    qApp->quit();
    QProcess::startDetached(qApp->arguments()[0], qApp->arguments());
}

void MainWindow::selectFolder()
{
    QString folderPath = QFileDialog::getExistingDirectory();

    if(folderPath == "")
        return;

    std::ofstream myfile;
    myfile.open ("folderToSync.txt");
    myfile << folderPath.toUtf8().constData();
    myfile.close();

    m_status->setText("Your are correctly synchronising the folder : " + folderPath);
    m_status->setStyleSheet("QLabel { color : green; }");
}
