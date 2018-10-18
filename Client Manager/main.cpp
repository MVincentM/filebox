#define WIN32

#include <iostream>
#include <fstream>
#include <string>
#include <filesystem>

#include <sys/types.h>
#include <sys/stat.h>
#ifndef WIN32
#include <unistd.h>
#endif
#ifdef WIN32
#define stat _stat
#endif

#include "document.h"
#include "writer.h"
#include "stringbuffer.h"

namespace fs = std::experimental::filesystem;

// sftp port : 14 147

int64_t getSyncDate(rapidjson::Document &d, std::string filename)
{
	std::cout << "filename = " << filename << "\n";
	int numberOfFiles = d.Size();
	for (int i(0); i < numberOfFiles; ++i)
		if (d[i]["path"].GetString() == filename)
			return d[i]["syncDate"].GetInt64();

	return -1;
}

bool sendFile(std::string filename)
{
	std::cout << "Need to re-send file (" << filename << ") !" << "\n";

	std::string directory;
	const size_t last_slash_idx = filename.rfind('\\');
	if (std::string::npos != last_slash_idx)
		directory = filename.substr(0, last_slash_idx);

	std::string filenameToUse = "65485156"; // Asking for filename
	std::string destination = directory + "\\" + filenameToUse;
	std::string cmd = "ECHO F | xcopy \"" + filename + "\" \"" + destination + "\" ";
	system(cmd.c_str());

	// Sending file
	cmd = "curl -T \"" + destination + "\" ftp://client:@127.0.0.1/";
	system(cmd.c_str());
	
	// Deleting the copy
	cmd = "DEL \"" + destination + "\" /F";
	system(cmd.c_str());

	return true;
}

bool getFile(std::string filename)
{
	std::cout << "Need to get new file (" << filename << ") !" << "\n";

	std::string filenameInFTP = "564853"; // Asking for coping file in temp folder

	std::string cmd = "curl -o \"" + filename + "\" \"ftp://client:@127.0.0.1/" + filenameInFTP + "\"";
	std::cout << cmd << "\n";
	system(cmd.c_str());

	return true;
}

void checkSyncDate(rapidjson::Document &d, std::string folderPath, std::string initialFolderPath)
{
	for (auto & p : fs::directory_iterator(folderPath))
	{
		std::string filename = p.path().string();
		if (filename.substr(initialFolderPath.size() + 1, SIZE_MAX) == ".filebox")
			continue;
		std::cout << filename << std::endl;
		if (fs::is_directory(p))
		{
			checkSyncDate(d, filename, initialFolderPath);
			continue;
		}
		struct stat result;
		if (stat(filename.c_str(), &result) == 0)
		{
			int64_t mod_time = result.st_mtime;
			int64_t syncDate = getSyncDate(d, filename.substr(initialFolderPath.size() + 1, SIZE_MAX));

			if (mod_time > syncDate)
				sendFile(filename);
			else if (syncDate > mod_time)
				getFile(filename);
		}
	}
}

int main(int argc, char** argv)
{
	if (argc < 3)
	{
		std::cout << "Need more arguments" << std::endl;
		return EXIT_FAILURE;
	}

	if (strcmp(argv[1], "sync") == 0)
	{
		std::string folderPath = argv[2];
		std::cout << "Synchronisation of the folder \"" << folderPath << "\"\n";

		std::string line, text;
		std::ifstream in(folderPath + "/.filebox");
		while (std::getline(in, line))
		{
			text += line + "\n";
		}
		const char* data = text.c_str();

		rapidjson::Document d;
		d.Parse(data);

		checkSyncDate(d, folderPath, folderPath);
	}

	return EXIT_SUCCESS;
}
