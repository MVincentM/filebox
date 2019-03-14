#include <QApplication>
#include "loginwindow.h"

int main(int argc, char *argv[])
{
    QApplication app(argc, argv);
    WebSocket *client = new WebSocket(QUrl(QStringLiteral("ws://localhost:1234")), true);

    LoginWindow loginWindow(client);
    loginWindow.show();

    return app.exec();
}
