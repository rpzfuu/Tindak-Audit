<template>
    <dialog ref="modal" class="modal">
        <div
            class="items-center justify-center p-8 text-center bg-white rounded-lg shadow-lg modal-box"
        >
            <v-icon
                name="hi-clipboard-check"
                class="mb-4 text-success"
                scale="4"
            ></v-icon>
            <h3 class="mb-2 text-2xl font-extrabold text-success">
                Cek Validasi
            </h3>
            <p class="mb-4 text-gray-600 text-md">
                Silahkan Hasil Validasi Oleh SPI
            </p>
            <div class="w-full p-4 mb-4 bg-gray-100 rounded-lg shadow-inner">
                <p class="mb-2 text-sm text-left text-gray-700">
                    <span class="font-bold">Temuan: </span>{{ temuan?.temuan }}
                </p>
                <p
                    v-if="temuan?.unit_usaha.kode_unit == '4R00'"
                    class="mb-2 text-sm text-left text-gray-700"
                >
                    <span class="font-bold">Tujuan Unit/Bagian: </span>
                    {{ temuan?.bagian.name }}
                </p>
                <p v-else class="mb-2 text-sm text-left text-gray-700">
                    <span class="font-bold">Tujuan Unit/Bagian: </span>
                    {{ temuan?.unit_usaha.nama_unit }}
                </p>
                <p class="mb-2 text-sm text-left text-gray-700">
                    <span class="font-bold">Bidang: </span
                    >{{ temuan?.bidang.nama }}
                </p>
                <p class="mb-2 text-sm text-left text-gray-700">
                    <span class="font-bold">Status: </span>{{ temuan?.status }}
                </p>
                <div
                    v-for="(rekomendasi, index) in temuan?.rekomendasi"
                    :key="index"
                    class="flex justify-between p-4 mb-6 border rounded-lg shadow-inner bg-gray-50"
                >
                    <div class="w-3/4 pr-4 text-left">
                        <div>
                            <p class="text-sm font-semibold">
                                Rekomendasi {{ index + 1 }}:
                            </p>
                            <p class="py-1 text-gray-700">
                                {{ rekomendasi?.rekomendasi }}
                            </p>
                        </div>
                        <br />
                        <div>
                            <p class="text-sm font-semibold">
                                Tindak Lanjut {{ index + 1 }}:
                            </p>
                            <p class="py-1 text-gray-700">
                                {{ rekomendasi?.tindak_lanjut }}
                            </p>
                        </div>
                    </div>
                    <div class="flex items-center justify-center w-1/4">
                        <span class="flex items-center p-2 border rounded-full">
                            {{ rekomendasi?.status }}
                        </span>
                    </div>
                </div>
            </div>

            <div class="flex justify-center space-x-4 modal-action">
                <form @submit.prevent="unitCekValidasi" class="flex-1">
                    <button class="w-full px-6 py-2 text-white btn btn-success">
                        Konfirmasi Hasil Validasi
                    </button>
                </form>
            </div>
        </div>
        <form method="dialog" class="modal-backdrop">
            <button>close</button>
        </form>
    </dialog>
</template>
<script lang="ts" setup>
import RequestAPI from "@/utils/RequestAPI";
import Toast from "@/utils/Toast";
import { ref } from "vue";

const props = defineProps<{ temuan?: any; nik: any }>();

const modal = ref();
const showModal = () => {
    modal.value?.showModal();
};
defineExpose({
    showModal,
});
const emits = defineEmits(["refresh"]);

const unitCekValidasi = async () => {
    try {
        const form = ref({
            temuan_id: props.temuan.id,
            rekomendasi: props.temuan.rekomendasi,
        });
        const res = await RequestAPI.unitCekValidasi({ data: form.value });
        Toast.showSuccess(res.message);
        emits("refresh");
        modal.value?.close();
    } catch (e: any) {
        Toast.showError(String(e.message));
        modal.value?.close();
    }
};
</script>
