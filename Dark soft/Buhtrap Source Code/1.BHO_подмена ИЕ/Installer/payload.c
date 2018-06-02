#include <windows.h>

unsigned int PayLoad[] = {
        0,
};

LPVOID GetPayload (PDWORD psize) {
    *psize = sizeof(PayLoad);
    return (LPVOID) PayLoad;
}
