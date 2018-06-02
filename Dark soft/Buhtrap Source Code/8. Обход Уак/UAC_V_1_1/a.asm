.686
.model flat,stdcall
option casemap:none

include \masm32\include\rtl.inc
include \masm32\include\ntdll.inc
includelib \masm32\lib\ntdll.lib
include du.mac
include \masm32\include\kernel32.inc
includelib \masm32\lib\kernel32.lib
include \masm32\include\msvcrt.inc
includelib \masm32\lib\msvcrt.lib

.data

message db 'Type the name of the file',0dh,0ah,0

.code
start proc
local pmem,hmem,cbytes,cbytes1:dword
local hfile:dword
local buffer[200h]:byte

invoke crt_printf,offset message
invoke crt_gets,addr buffer
invoke CreateFile,addr buffer,GENERIC_ALL,0,0,OPEN_EXISTING,0,0
test eax,eax
js @r

mov hfile,eax
mov ebx,(end_-begin)
invoke GetFileSize,eax,0
push eax
cmp eax,200h
jae @f
mov eax,200h

@@:
lea esi,[eax+ebx]
invoke GetProcessHeap
mov hmem,eax
invoke HeapAlloc,hmem,HEAP_ZERO_MEMORY,esi
mov pmem,eax

mov ecx,ebx
mov esi,offset begin
mov edi,eax
rep movsb

mov esi,eax
mov edi,eax
add edi,(IMAGE_DOS_HEADER ptr [esi]).e_lfanew

movzx edx,(IMAGE_NT_HEADERS ptr [edi]).FileHeader.SizeOfOptionalHeader
lea edx,[edi+edx+sizeof IMAGE_FILE_HEADER+4]

mov eax,(IMAGE_SECTION_HEADER ptr [edx]).SizeOfRawData
pop esi
add eax,esi
test eax,1ffh
je @f
and eax,0fffffe00h
add eax,200h
@@:
mov (IMAGE_NT_HEADERS ptr [edi]).OptionalHeader.SizeOfCode,eax
mov (IMAGE_SECTION_HEADER ptr [edx]).SizeOfRawData,eax
sub eax,100h
mov (IMAGE_SECTION_HEADER ptr [edx]).Misc,eax


mov eax,(IMAGE_NT_HEADERS ptr [edi]).OptionalHeader.SizeOfCode
add eax,1000h
test eax,0fffh
jz @f
add eax,1000h
and eax,0fffff000h
@@:

mov (IMAGE_NT_HEADERS ptr [edi]).OptionalHeader.SizeOfImage,eax

mov edi,pmem
add edi,ebx
mov [edi-4],esi
mov eax,pmem

invoke ReadFile,hfile,edi,esi,addr cbytes,0
invoke CloseHandle,hfile

lea ecx,buffer
@@:
cmp byte ptr [ecx],0
je @f
inc ecx
jmp @b

@@:
mov dword ptr [ecx-4],'xe.1'
mov word ptr [ecx],'e'

invoke CreateFile,addr buffer,GENERIC_ALL,0,0,CREATE_ALWAYS,0,0
mov hfile,eax

mov eax,esi
mov ecx,esi
shr ecx,2
xor eax,-1
dec ecx

mov dx,[edi]
or dx,2020h
cmp dx,'zm'
je @f

mov word ptr [edi-6],'BB'
xor dword ptr [edi],-1
jmp @add

@@:
mov word ptr [edi],'AA'

@add:
add edi,4

@@:
cmp dword ptr [edi],0
je @3
xor [edi],eax
cmp dword ptr [edi],0
jnz @3
xor [edi],eax
@3:
add edi,4
dec ecx
jnle @b

cmp esi,200h
jae @f

mov esi,200h

@@:
add esi,ebx
invoke WriteFile,hfile,pmem,esi,addr cbytes,0
invoke CloseHandle,hfile

invoke HeapFree,hmem,HEAP_NO_SERIALIZE,pmem
@r:
ret
start endp
begin:
include dump.txt
end_:
end start
