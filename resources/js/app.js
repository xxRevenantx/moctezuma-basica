
Livewire.on('swal', (data) => {
          const Toast = Swal.mixin({
          toast: true,
          position: data[0].position,
          showConfirmButton: false,
          timer: 3000,
          timerProgressBar: true,
          didOpen: (toast) => {
          toast.addEventListener('mouseenter', Swal.stopTimer)
          toast.addEventListener('mouseleave', Swal.resumeTimer)
          }
      })

      Toast.fire({
          icon:data[0].icon,
          title: data[0].title,
      })
})

