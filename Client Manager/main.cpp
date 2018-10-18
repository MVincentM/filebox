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
				std::cout << "Need to re-send file !" << "\n";
			else if(syncDate > mod_time)
				std::cout << "Need to get new file !" << "\n";
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
