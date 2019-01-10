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

int64_t getSyncDate(rapidjson::Document &fileboxJSON, std::string filename)
{
	int numberOfFiles = fileboxJSON.Size();
	for (int i(0); i < numberOfFiles; ++i)
		if (fileboxJSON[i]["path"].GetString() == filename)
			return fileboxJSON[i]["syncDate"].GetInt64();

	return -1;
}

int64_t getLocalDate(rapidjson::Document &localInfoJSON, std::string filename)
{
	int numberOfFiles = localInfoJSON.Size();
	for (int i(0); i < numberOfFiles; ++i)
		if (localInfoJSON[i]["path"].GetString() == filename)
			return localInfoJSON[i]["localDate"].GetInt64();

	return -1;
}

bool sendFile(std::string filename, rapidjson::Document &localInfoJSON, int64_t localDate, std::string shortFilename, std::string localInfo)
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
	cmd = "curl.exe -T \"" + destination + "\" ftp://client:@127.0.0.1/";
	system(cmd.c_str());
	
	// Deleting the copy
	cmd = "DEL \"" + destination + "\" /F";
	system(cmd.c_str());

	bool modified = false;
	int numberOfFiles = localInfoJSON.Size();
	for (int i(0); i < numberOfFiles; ++i)
		if (localInfoJSON[i]["path"].GetString() == filename)
		{
			localInfoJSON[i]["localDate"].SetInt64(localDate);
			modified = true;
		}
	
	if (!modified)
	{
		std::string objectToAdd = ",{ ";
		objectToAdd += "\"path\" : \"" + shortFilename + "\",";
		objectToAdd += "\"localDate\" : " + std::to_string(localDate) + "}";

		{
			std::ifstream in(localInfo);
			std::ofstream out(localInfo + "tmp");
			
			std::string line;
			while (getline(in, line)) 
			{
				if (line.find("]") == std::string::npos)
					out << line << '\n';
			}

			out.close();
			in.close();
		}

		cmd = "DEL \"" + localInfo + "\" /F";
		system(cmd.c_str());
		std::string cmd = "ECHO F | xcopy \"" + localInfo + "tmp" + "\" \"" + localInfo + "\" ";
		system(cmd.c_str());

		std::ofstream outfile;
		outfile.open(localInfo, std::ios_base::app);
		outfile << objectToAdd;
		outfile << "\n]";
		outfile.close();
	}

	return true;
}

bool getFile(std::string filename)
{
	std::cout << "Need to get new file (" << filename << ") !" << "\n";

	std::string filenameInFTP = "564853"; // Asking for coping file in temp folder

	std::string cmd = "curl.exe -o \"" + filename + "\" \"ftp://client:@127.0.0.1/" + filenameInFTP + "\"";
	std::cout << cmd << "\n";
	system(cmd.c_str());

	return true;
}

void checkSyncDate(rapidjson::Document &fileboxJSON, rapidjson::Document &localInfoJSON, std::string folderPath, std::string initialFolderPath, 
	std::string localFile)
{
	for (auto & p : fs::directory_iterator(folderPath)) // For each files
	{
		std::string filename = p.path().string();
		if (filename.substr(initialFolderPath.size() + 1, SIZE_MAX) == ".filebox") // filebox file -> no sync
			continue;
		std::cout << "Filename : " << filename << std::endl;
		if (fs::is_directory(p)) // it's a directory -> re call function
		{
			std::cout << "Entering directory.." << std::endl;
			checkSyncDate(fileboxJSON, localInfoJSON, filename, initialFolderPath, localFile);
			continue;
		}
		struct stat result;
		if (stat(filename.c_str(), &result) == 0)
		{
			int64_t mod_time = result.st_mtime;
			int64_t syncDate = getSyncDate(fileboxJSON, filename.substr(initialFolderPath.size() + 1, SIZE_MAX));
			int64_t localDate = getLocalDate(localInfoJSON, filename.substr(initialFolderPath.size() + 1, SIZE_MAX));

			if (mod_time > localDate)
				sendFile(filename, localInfoJSON, localDate, filename.substr(initialFolderPath.size() + 1, SIZE_MAX), localFile);
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

		rapidjson::Document fileboxJSON;
		fileboxJSON.Parse(data);

		line = "", text = "";
		std::ifstream inLocal(folderPath + "/.localInfo");
		while (std::getline(inLocal, line))
		{
			text += line + "\n";
		}
		const char* dataLocal = text.c_str();

		rapidjson::Document localInfoJSON;
		localInfoJSON.Parse(dataLocal);

		checkSyncDate(fileboxJSON, localInfoJSON, folderPath, folderPath, folderPath + "/.localInfo");
	}

	std::cout << "End" << "\n";
	return EXIT_SUCCESS;
}
