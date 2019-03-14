#ifndef WEBSOCKET_H
#define WEBSOCKET_H

#include <QtCore/QObject>
#include <QDir>
#include <QtWebSockets/QWebSocket>
#include <QFileInfo>
#include <QJsonDocument>
#include <QJsonObject>
#include <QJsonArray>
#include <QNetworkAccessManager>
#include <QNetworkReply>
#include <QThread>

class WebSocket : public QObject
{
Q_OBJECT
public:
    explicit WebSocket(const QUrl &url, bool debug = false, QObject *parent = nullptr);
    void sendMessage(std::string message);
    void checkSync(std::string folderPath = "");
    void setFolderPath(std::string folderPath) { m_folderPath = folderPath; }
    void setAuthKey(std::string authKey) { m_authKey = authKey; }
    std::string getAuthKey() { return m_authKey; }
    void setJsonFileIn(std::string json) { m_jsonFileInfo = json; m_needReloadJSON = false; }
    void setRootID(std::string rootID) { m_rootID = rootID; }
    std::string getRootID() { return m_rootID; }
    bool getNeedReloadJSON() { return m_needReloadJSON; }

    void setEmail(const std::string &email);
    void setPassword(const std::string &password);

Q_SIGNALS:
    void closed();

private Q_SLOTS:
    void onConnected();
    void onTextMessageReceived(QString message);
    void onResult(QNetworkReply*);

private:
    QWebSocket m_webSocket;
    QUrl m_url;
    bool m_debug;
    std::string m_email = "";
    std::string m_password = "";
    std::string m_folderPath = "";
    std::string m_authKey = "";
    std::string m_jsonFileInfo = "";
    std::string m_rootID = "";
    bool m_needReloadJSON = false;
    std::vector<std::pair<uint, uint>> m_dateMapping;

    QNetworkAccessManager *m_networkManager;
    QNetworkRequest m_request;
    QFile *m_file;
    QString m_filenameToPush = "";
    QString m_filenameToPull = "";
    uint m_lastModifiedDateToPull = 0;

    std::vector<std::string> m_operations;
};

#endif // WEBSOCKET_H
