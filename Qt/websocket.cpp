#include "websocket.h"

QT_USE_NAMESPACE

WebSocket::WebSocket(const QUrl &url, bool debug, QObject *parent) :
    QObject(parent),
    m_url(url),
    m_debug(debug)
{
    if (m_debug)
        qDebug() << "WebSocket server:" << url;
    connect(&m_webSocket, &QWebSocket::connected, this, &WebSocket::onConnected);
    connect(&m_webSocket, &QWebSocket::disconnected, this, &WebSocket::closed);
    m_webSocket.open(QUrl(url));

    m_networkManager = new QNetworkAccessManager();
    connect(m_networkManager, SIGNAL(finished(QNetworkReply*)), this, SLOT(onResult(QNetworkReply*)));

    QFile fileMapDate("mapDate.txt");
    if (fileMapDate.open(QIODevice::ReadOnly))
    {
        QTextStream in(&fileMapDate);
        while (!in.atEnd())
        {
            QString line = in.readLine();
            QStringList vals = line.split(":");
            m_dateMapping.push_back({vals[0].toUInt(), vals[1].toUInt()});
        }
        fileMapDate.close();
    }
}

void WebSocket::checkSync(std::string folderPath)
{
    qDebug() << "Key = " << QString::fromStdString(m_authKey);
    //qDebug() << "JSON = " << QString::fromStdString(m_jsonFileInfo);
    if(folderPath == "")
        folderPath = m_folderPath;

    qDebug() << "Check sync" << QString::fromStdString(folderPath);
    QDir directory(QString::fromStdString(folderPath));
    QStringList fileList = directory.entryList();
    QJsonDocument d = QJsonDocument::fromJson(QString::fromStdString(m_jsonFileInfo).toUtf8());
    QJsonArray jsonArray = d.array();
    foreach(QString filename, fileList)
    {
        if(filename[0] == '.')
            continue;
        qDebug() << "Checking filename" << filename;
        bool fileExistsInDBB = false;
        QFileInfo info(QString::fromStdString(folderPath) + "/" + filename);
        if(!info.isFile())
        {
            checkSync(folderPath + "/" + filename.toStdString());
            continue;
        }
        uint lastModifiedDate = info.lastModified().toTime_t();
        std::string folderName = folderPath.substr(m_folderPath.size());
        if(folderName != "")
            folderName = folderName.substr(1);
        //qDebug() << QString::fromStdString(folderName);

        for(int j = 0; j < jsonArray.count(); j++)
        {
            if(jsonArray[j].toObject().value("type") == "file" &&
                    jsonArray[j].toObject().value("name").toString() == filename &&
                    jsonArray[j].toObject().value("path").toString() == QString::fromStdString(folderName))
            {
                fileExistsInDBB = true;
                uint lastModifiedJson = (uint)jsonArray[j].toObject().value("lastUpdate").toInt();
                for (size_t k = 0; k < m_dateMapping.size(); ++k)
                {
                    if(m_dateMapping[k].second == lastModifiedJson)
                        lastModifiedJson = m_dateMapping[k].first;
                }
                if(lastModifiedDate > lastModifiedJson)
                {
                    qDebug() << "Need to send : " << lastModifiedDate;

                    std::string url = "http://127.0.0.1:8000/api/update/template?authkey=" +
                            m_authKey + "&type=file&nameFile=" + filename.toStdString() + "&path=" +
                            folderName + "&dateLastUpdate=";
                    url += std::to_string(lastModifiedDate);

                    qDebug() << "URL = " << QString::fromStdString(url);

                    QUrl qurl(QString::fromStdString(url));
                    m_request.setUrl(qurl);

                    m_needReloadJSON = true;
                    m_operations.push_back("updateFileInDB");
                    m_filenameToPush = QString::fromStdString(folderPath) + "/" + filename;
                    m_networkManager->get(m_request);
                }
                else if(lastModifiedJson > lastModifiedDate)
                {
                    qDebug() << "Need to get : " << lastModifiedDate;

                    std::string url = "http://127.0.0.1:8000/api/get/id/template?authkey=" +
                            m_authKey + "&nameFile=" + filename.toStdString() + "&path=" + folderName;

                    QUrl qurl(url.c_str());
                    m_request.setUrl(qurl);

                    m_operations.push_back("getIdFileInDB");
                    m_filenameToPull = QString::fromStdString(folderPath) + "/" + filename;
                    m_lastModifiedDateToPull = lastModifiedJson;
                    m_networkManager->get(m_request);
                }
            }
        }
        if(!fileExistsInDBB)
        {
            qDebug() << "Need to create file !";
            std::string url = "http://127.0.0.1:8000/api/insert/template?authkey=" +
                    m_authKey + "&type=file&nameFile=" + filename.toStdString() + "&path=" +
                    folderName + "&dateLastUpdate=";
            url += std::to_string(lastModifiedDate);

            QUrl qurl(url.c_str());
            m_request.setUrl(qurl);

            m_needReloadJSON = true;
            m_operations.push_back("createFileInDB");
            m_networkManager->get(m_request);
        }
    }
}

void WebSocket::onResult(QNetworkReply* reply)
{
    if (reply->error())
    {
        qDebug() << "Error for operation " << QString::fromStdString(m_operations[0]) << " = " << reply->errorString();
        return;
    }

    QString answer = reply->readAll();
    qDebug() << "Answer for operation " << QString::fromStdString(m_operations[0]) << " = " << answer;

    if(m_operations[0] == "createFileInDB" || m_operations[0] == "updateFileInDB")
    {
        QUrl url("ftp://127.0.0.1/home/ftp_folder/" + answer);
        url.setUserName(QString::fromStdString(m_email));
        url.setPassword(QString::fromStdString(m_password));
        url.setPort(21);

        qDebug() << "Push file : " << m_filenameToPush;
        m_file = new QFile(m_filenameToPush);
        if (m_file->open(QIODevice::ReadOnly))
        {
            m_operations.push_back("uploadFileToFTP");
            m_networkManager->put(QNetworkRequest(url), m_file);
        }
    }
    else if(m_operations[0] == "getIdFileInDB")
    {
        QUrl url("ftp://127.0.0.1/home/ftp_folder/" + answer);
        url.setUserName(QString::fromStdString(m_email));
        url.setPassword(QString::fromStdString(m_password));
        url.setPort(21);

        m_operations.push_back("pullFileFromFTP");
        m_networkManager->get(QNetworkRequest(url));
    }
    else if(m_operations[0] == "pullFileFromFTP")
    {
        qDebug() << "Writing to " << m_filenameToPull;
        QFile fileToPull(m_filenameToPull);
        fileToPull.open(QIODevice::ReadWrite);

        fileToPull.seek(0);
        fileToPull.write(answer.toUtf8());

        fileToPull.close();

        QFileInfo info(m_filenameToPull);
        uint lastModifiedDateToWrite = info.lastModified().toTime_t();

        QFile fileMappingDate("mapDate.txt");
        fileMappingDate.open(QIODevice::Append);

        std::string line = std::to_string(lastModifiedDateToWrite) + ":" + std::to_string(m_lastModifiedDateToPull);
        fileMappingDate.write(line.c_str());

        fileMappingDate.close();

        m_dateMapping.push_back({lastModifiedDateToWrite, m_lastModifiedDateToPull});
    }

    m_operations.erase (m_operations.begin());
}

void WebSocket::setPassword(const std::string &password)
{
    m_password = password;
}

void WebSocket::setEmail(const std::string &email)
{
    m_email = email;
}

void WebSocket::onConnected()
{
    if (m_debug)
        qDebug() << "WebSocket connected";
    connect(&m_webSocket, &QWebSocket::textMessageReceived,
            this, &WebSocket::onTextMessageReceived);
}

void WebSocket::onTextMessageReceived(QString message)
{
    if (m_debug)
        qDebug() << "Message received:" << message;
}

void WebSocket::sendMessage(std::string message)
{
   m_webSocket.sendTextMessage(QString(message.c_str()));
}
