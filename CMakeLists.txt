cmake_minimum_required(VERSION 3.5)

project(words LANGUAGES CXX)

find_package(SndFile REQUIRED)

set(CMAKE_CXX_STANDARD 11)
set(CMAKE_CXX_STANDARD_REQUIRED ON)

include_directories(${SNDFILE_INCLUDE_DIRS})
add_executable(words main.cpp)
target_link_libraries(words ${SNDFILE_LIBRARIES})
